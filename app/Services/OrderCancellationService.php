<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\RateMaster;
use App\Models\Refund;
use App\Models\StockInfo;
use App\Services\Payment\RazorpayService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class OrderCancellationService
{
    protected OrderStatusService $orderStatusService;
    protected RazorpayService $razorpayService;

    public function __construct(OrderStatusService $orderStatusService, RazorpayService $razorpayService)
    {
        $this->orderStatusService = $orderStatusService;
        $this->razorpayService = $razorpayService;
    }

    public function cancelByCustomer(int $orderId, int $customerId): void
    {
        $this->requestByCustomer($orderId, $customerId);
    }

    public function requestByCustomer(int $orderId, int $customerId): void
    {
        DB::transaction(function () use ($orderId, $customerId) {
            $order = Order::query()
                ->with(['payments', 'refunds', 'statuses'])
                ->where('id', $orderId)
                ->where('customer_id', $customerId)
                ->where('is_active', 1)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                throw new RuntimeException('Order not found.');
            }

            $latestStatus = $this->orderStatusService->getLatestStatusForOrder($order);
            $currentStatus = $latestStatus ? $latestStatus->order_status : null;

            if (!$this->orderStatusService->canCustomerCancel($currentStatus)) {
                throw new RuntimeException('This order cannot be cancelled now.');
            }

            $payment = $this->getSuccessfulPayment($order);

            if (!$payment) {
                throw new RuntimeException('Only paid orders can be cancelled from this page.');
            }

            $alreadyRequested = $order->refunds
                ->whereIn('status', ['requested', 'approved', 'queued', 'pending', 'processed'])
                ->isNotEmpty();

            if ($alreadyRequested) {
                throw new RuntimeException('Cancellation is already requested for this order.');
            }

            Refund::create([
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'customer_id' => $customerId,
                'gateway' => 'razorpay',
                'amount' => (float) $payment->amount,
                'currency' => (string) $payment->currency,
                'status' => 'requested',
                'reason' => 'Customer requested cancellation',
                'requested_by_id' => $customerId,
                'requested_at' => Carbon::now(),
                'created_by_id' => $customerId,
                'created_date' => Carbon::now(),
            ]);

            $order->updated_by_id = $customerId;
            $order->updated_date = Carbon::now();
            $order->save();

            $payment->status = 'refund_requested';
            $payment->updated_by_id = $customerId;
            $payment->updated_date = Carbon::now();
            $payment->save();

            $this->orderStatusService->addCancellationRequestedStatus($order, $customerId);
        });
    }

    public function approveByAdmin(int $orderId, int $adminId): void
    {
        $approvalData = DB::transaction(function () use ($orderId, $adminId) {
            $order = Order::query()
                ->with(['payments', 'refunds', 'statuses'])
                ->where('id', $orderId)
                ->where('is_active', 1)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                throw new RuntimeException('Order not found.');
            }

            $latestStatus = $this->orderStatusService->getLatestStatusForOrder($order);
            $currentStatus = $latestStatus ? $latestStatus->order_status : null;

            if ($currentStatus !== OrderStatusService::STATUS_CANCELLATION_REQUESTED) {
                throw new RuntimeException('This order does not have a pending cancellation request.');
            }

            $refund = $order->refunds
                ->whereIn('status', ['requested', 'failed'])
                ->sortByDesc('id')
                ->first();

            if (!$refund) {
                throw new RuntimeException('Refund request not found.');
            }

            $payment = $order->payments
                ->where('id', $refund->payment_id)
                ->whereIn('status', ['paid', 'refund_requested'])
                ->first();

            if (!$payment || empty($payment->gateway_payment_id)) {
                throw new RuntimeException('Paid Razorpay payment not found for this order.');
            }

            $refund->status = 'approved';
            $refund->approved_by_id = $adminId;
            $refund->approved_at = Carbon::now();
            $refund->updated_by_id = $adminId;
            $refund->updated_date = Carbon::now();
            $refund->save();

            return [
                'order_id' => (int) $order->id,
                'payment_id' => (int) $payment->id,
                'gateway_payment_id' => (string) $payment->gateway_payment_id,
                'amount_in_paise' => (int) round((float) $refund->amount * 100),
                'receipt' => 'cancel_order_' . $order->id . '_refund_' . $refund->id,
            ];
        });

        try {
            $razorpayRefund = $this->razorpayService->refundPayment(
                $approvalData['gateway_payment_id'],
                $approvalData['amount_in_paise'],
                $approvalData['receipt']
            );
        } catch (Throwable $exception) {
            DB::transaction(function () use ($approvalData, $adminId) {
                Refund::query()
                    ->where('order_id', $approvalData['order_id'])
                    ->where('payment_id', $approvalData['payment_id'])
                    ->where('status', 'approved')
                    ->update([
                        'status' => 'failed',
                        'failed_reason' => 'Razorpay refund could not be started.',
                        'updated_by_id' => $adminId,
                        'updated_date' => Carbon::now(),
                    ]);
            });

            throw new RuntimeException('Refund could not be started. Please try again later.');
        }

        DB::transaction(function () use ($approvalData, $razorpayRefund, $adminId) {
            $order = Order::query()
                ->with(['items', 'statuses'])
                ->where('id', $approvalData['order_id'])
                ->lockForUpdate()
                ->firstOrFail();

            foreach ($order->items as $item) {
                $rate = RateMaster::find((int) $item->rate_master_id);

                if (!$rate || $rate->stock_dependent !== 'YES') {
                    continue;
                }

                $currentStock = $this->getLockedCurrentStock((int) $item->rate_master_id);
                $newStock = $currentStock + (int) $item->quantity;

                StockInfo::create([
                    'rate_master_id' => (int) $item->rate_master_id,
                    'stock_in_count' => (int) $item->quantity,
                    'sale_quantity' => 0,
                    'current_stock' => $newStock,
                    'sale_order_id' => $order->id,
                    'is_active' => 1,
                    'created_by_id' => $adminId,
                    'created_date' => Carbon::now(),
                ]);

                $this->syncSoldOutStatus((int) $item->rate_master_id, $newStock, $adminId);
            }

            Refund::query()
                ->where('order_id', $approvalData['order_id'])
                ->where('payment_id', $approvalData['payment_id'])
                ->where('status', 'approved')
                ->update([
                    'gateway_refund_id' => $razorpayRefund['id'],
                    'status' => $razorpayRefund['status'],
                    'processed_at' => $razorpayRefund['status'] === 'processed' ? Carbon::now() : null,
                    'updated_by_id' => $adminId,
                    'updated_date' => Carbon::now(),
                ]);

            Payment::query()
                ->where('id', $approvalData['payment_id'])
                ->update([
                    'status' => $razorpayRefund['status'] === 'processed'
                        ? 'refunded'
                        : 'refund_pending',
                    'updated_by_id' => $adminId,
                    'updated_date' => Carbon::now(),
                ]);

            $order->updated_by_id = $adminId;
            $order->updated_date = Carbon::now();
            $order->save();

            $this->orderStatusService->addCustomerCancelledStatus($order, (int) $order->customer_id);
        });
    }

    private function getSuccessfulPayment(Order $order): ?Payment
    {
        return $order->payments
            ->where('status', 'paid')
            ->sortByDesc('id')
            ->first();
    }

    private function getLockedCurrentStock(int $rateMasterId): int
    {
        $latestStock = StockInfo::query()
            ->where('rate_master_id', $rateMasterId)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        if (!$latestStock) {
            return 0;
        }

        return (int) $latestStock->current_stock;
    }

    private function syncSoldOutStatus(int $rateMasterId, int $currentStock, int $userId): void
    {
        RateMaster::query()
            ->where('id', $rateMasterId)
            ->update([
                'soldout_status' => $currentStock > 0 ? 'NO' : 'YES',
                'updated_by_id' => $userId,
                'updated_date' => Carbon::now(),
            ]);
    }
}
