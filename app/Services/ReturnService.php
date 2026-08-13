<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\RateMaster;
use App\Models\Refund;
use App\Models\ReturnItem;
use App\Models\ReturnRequest;
use App\Models\StockInfo;
use App\Services\Payment\RazorpayService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ReturnService
{
    public const RETURN_WINDOW_DAYS = 7;

    public const REASONS = [
        'Wrong Item',
        'Damaged Product',
        'Defective Product',
        'Missing Parts',
        'Not as Described',
        'Ordered by Mistake',
        'Other',
    ];

    protected OrderStatusService $orderStatusService;
    protected RazorpayService $razorpayService;

    public function __construct(OrderStatusService $orderStatusService, RazorpayService $razorpayService)
    {
        $this->orderStatusService = $orderStatusService;
        $this->razorpayService = $razorpayService;
    }

    public function getReasons(): array
    {
        return self::REASONS;
    }

    public function getReturnsForAdmin(): LengthAwarePaginator
    {
        return ReturnRequest::query()
            ->with(['order', 'customer', 'items.product', 'refunds'])
            ->orderByDesc('requested_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function getReturnForAdmin(int $returnId): ?ReturnRequest
    {
        return ReturnRequest::query()
            ->with([
                'order.customer',
                'order.address',
                'customer',
                'items.product',
                'items.rate',
                'refunds',
            ])
            ->where('id', $returnId)
            ->first();
    }

    public function canCustomerRequestReturn(Order $order): bool
    {
        if (!$order->is_active) {
            return false;
        }

        $latestStatus = $this->orderStatusService->getLatestStatusForOrder($order);
        $currentStatus = $latestStatus ? $latestStatus->order_status : null;

        if (in_array($currentStatus, [
            OrderStatusService::STATUS_CANCELLATION_REQUESTED,
            OrderStatusService::STATUS_CANCELLED_BY_CUSTOMER,
        ], true)) {
            return false;
        }

        if (!$this->getDeliveredAt($order)) {
            return false;
        }

        if ($this->isReturnPeriodExpired($order)) {
            return false;
        }

        return $this->getReturnableItems($order)->isNotEmpty()
            && !$order->returnRequests
                ->whereNotIn('status', ['return_rejected', 'inspection_failed', 'return_closed'])
                ->isNotEmpty();
    }

    public function isReturnPeriodExpired(Order $order): bool
    {
        $deliveredAt = $this->getDeliveredAt($order);

        return $deliveredAt ? $deliveredAt->copy()->addDays(self::RETURN_WINDOW_DAYS)->isPast() : true;
    }

    public function returnAllowedUntil(Order $order): ?Carbon
    {
        $deliveredAt = $this->getDeliveredAt($order);

        if (!$deliveredAt) {
            return null;
        }

        return $deliveredAt->copy()->addDays(self::RETURN_WINDOW_DAYS);
    }

    public function requestByCustomer(int $orderId, int $customerId, string $reason, ?string $customerNote, array $requestedItems): ReturnRequest
    {
        return DB::transaction(function () use ($orderId, $customerId, $reason, $customerNote, $requestedItems) {
            $order = Order::query()
                ->with(['items.product', 'items.rate', 'statuses', 'payments', 'returnRequests.items'])
                ->where('id', $orderId)
                ->where('customer_id', $customerId)
                ->where('is_active', 1)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                throw new RuntimeException('Order not found.');
            }

            if (!$this->canCustomerRequestReturn($order)) {
                throw new RuntimeException($this->isReturnPeriodExpired($order)
                    ? 'Return period expired for this order.'
                    : 'This order cannot be returned now.');
            }

            if (!in_array($reason, self::REASONS, true)) {
                throw new RuntimeException('Invalid return reason selected.');
            }

            $payment = $this->getSuccessfulPayment($order);

            if (!$payment) {
                throw new RuntimeException('Only paid delivered orders can be returned.');
            }

            $selectedItems = $this->buildSelectedReturnItems($order, $requestedItems, $customerId);

            if ($selectedItems->isEmpty()) {
                throw new RuntimeException('Please select at least one item to return.');
            }

            $refundAmount = round((float) $selectedItems->sum('line_total'), 2);

            if ($refundAmount <= 0) {
                throw new RuntimeException('Selected return items have no refundable amount.');
            }

            $return = ReturnRequest::create([
                'order_id' => $order->id,
                'customer_id' => $customerId,
                'reason' => $reason,
                'customer_note' => $customerNote,
                'status' => OrderStatusService::STATUS_RETURN_REQUESTED,
                'refund_amount' => $refundAmount,
                'requested_at' => Carbon::now(),
                'created_by_id' => $customerId,
                'created_date' => Carbon::now(),
            ]);

            foreach ($selectedItems as $selectedItem) {
                ReturnItem::create(array_merge($selectedItem, [
                    'return_id' => $return->id,
                ]));
            }

            $this->orderStatusService->addSystemStatus(
                $order,
                OrderStatusService::STATUS_RETURN_REQUESTED,
                $customerId
            );

            return $return;
        });
    }

    public function approveByAdmin(int $returnId, int $adminId, ?string $adminNote = null): void
    {
        $this->transitionByAdmin($returnId, $adminId, 'return_requested', 'return_approved', [
            'approved_by_id' => $adminId,
            'approved_at' => Carbon::now(),
            'admin_note' => $adminNote,
        ]);
    }

    public function rejectByAdmin(int $returnId, int $adminId, ?string $adminNote = null): void
    {
        $this->transitionByAdmin($returnId, $adminId, 'return_requested', 'return_rejected', [
            'rejected_at' => Carbon::now(),
            'admin_note' => $adminNote,
            'closed_at' => Carbon::now(),
        ]);
    }

    public function schedulePickupByAdmin(int $returnId, int $adminId): void
    {
        $this->transitionByAdmin($returnId, $adminId, 'return_approved', 'pickup_scheduled', [
            'pickup_scheduled_at' => Carbon::now(),
        ]);
    }

    public function markReceivedByAdmin(int $returnId, int $adminId): void
    {
        $this->transitionByAdmin($returnId, $adminId, 'pickup_scheduled', 'product_received', [
            'received_at' => Carbon::now(),
        ]);
    }

    public function failInspectionByAdmin(int $returnId, int $adminId, ?string $adminNote = null): void
    {
        $this->transitionByAdmin($returnId, $adminId, 'product_received', 'inspection_failed', [
            'inspected_at' => Carbon::now(),
            'admin_note' => $adminNote,
            'sellable_stock' => false,
            'closed_at' => Carbon::now(),
        ]);
    }

    public function passInspectionAndRefundByAdmin(int $returnId, int $adminId, bool $sellableStock): void
    {
        $refundData = DB::transaction(function () use ($returnId, $adminId, $sellableStock) {
            $return = ReturnRequest::query()
                ->with(['order.payments', 'items'])
                ->where('id', $returnId)
                ->lockForUpdate()
                ->first();

            if (!$return) {
                throw new RuntimeException('Return request not found.');
            }

            if ($return->status !== 'product_received') {
                throw new RuntimeException('Product must be received before inspection can pass.');
            }

            $payment = $this->getSuccessfulPayment($return->order);

            if (!$payment || empty($payment->gateway_payment_id)) {
                throw new RuntimeException('Paid Razorpay payment not found for this return.');
            }

            $return->status = 'refund_initiated';
            $return->sellable_stock = $sellableStock;
            $return->inspected_at = Carbon::now();
            $return->updated_by_id = $adminId;
            $return->updated_date = Carbon::now();
            $return->save();

            $refund = Refund::create([
                'return_id' => $return->id,
                'payment_id' => $payment->id,
                'order_id' => $return->order_id,
                'customer_id' => $return->customer_id,
                'gateway' => 'razorpay',
                'amount' => (float) $return->refund_amount,
                'currency' => (string) $payment->currency,
                'status' => 'approved',
                'reason' => 'Return approved after inspection',
                'requested_by_id' => $return->customer_id,
                'requested_at' => $return->requested_at,
                'approved_by_id' => $adminId,
                'approved_at' => Carbon::now(),
                'created_by_id' => $adminId,
                'created_date' => Carbon::now(),
            ]);

            return [
                'return_id' => (int) $return->id,
                'refund_id' => (int) $refund->id,
                'payment_id' => (int) $payment->id,
                'gateway_payment_id' => (string) $payment->gateway_payment_id,
                'amount_in_paise' => (int) round((float) $return->refund_amount * 100),
                'receipt' => 'return_' . $return->id . '_refund_' . $refund->id,
                'sellable_stock' => $sellableStock,
                'refund_amount' => (float) $return->refund_amount,
                'payment_amount' => (float) $payment->amount,
            ];
        });

        try {
            $razorpayRefund = $this->razorpayService->refundPayment(
                $refundData['gateway_payment_id'],
                $refundData['amount_in_paise'],
                $refundData['receipt']
            );
        } catch (Throwable $exception) {
            DB::transaction(function () use ($refundData, $adminId) {
                Refund::query()
                    ->where('id', $refundData['refund_id'])
                    ->update([
                        'status' => 'failed',
                        'failed_reason' => 'Razorpay refund could not be started.',
                        'updated_by_id' => $adminId,
                        'updated_date' => Carbon::now(),
                    ]);

                ReturnRequest::query()
                    ->where('id', $refundData['return_id'])
                    ->update([
                        'status' => 'product_received',
                        'updated_by_id' => $adminId,
                        'updated_date' => Carbon::now(),
                    ]);
            });

            throw new RuntimeException('Refund could not be started. Please try again later.');
        }

        DB::transaction(function () use ($refundData, $razorpayRefund, $adminId) {
            $return = ReturnRequest::query()
                ->with('order', 'items')
                ->where('id', $refundData['return_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($refundData['sellable_stock']) {
                $this->restoreSellableStock($return, $adminId);
            }

            Refund::query()
                ->where('id', $refundData['refund_id'])
                ->update([
                    'gateway_refund_id' => $razorpayRefund['id'],
                    'status' => $razorpayRefund['status'],
                    'processed_at' => $razorpayRefund['status'] === 'processed' ? Carbon::now() : null,
                    'updated_by_id' => $adminId,
                    'updated_date' => Carbon::now(),
                ]);

            Payment::query()
                ->where('id', $refundData['payment_id'])
                ->update([
                    'status' => $razorpayRefund['status'] === 'processed'
                        ? ($refundData['refund_amount'] >= $refundData['payment_amount'] ? 'refunded' : 'partially_refunded')
                        : 'refund_pending',
                    'updated_by_id' => $adminId,
                    'updated_date' => Carbon::now(),
                ]);

            $return->status = $razorpayRefund['status'] === 'processed' ? 'return_closed' : 'refund_initiated';
            $return->closed_at = $razorpayRefund['status'] === 'processed' ? Carbon::now() : null;
            $return->updated_by_id = $adminId;
            $return->updated_date = Carbon::now();
            $return->save();

            $this->orderStatusService->addSystemStatus($return->order, 'inspection_passed', $adminId);
            $this->orderStatusService->addSystemStatus($return->order, 'refund_initiated', $adminId);

            if ($razorpayRefund['status'] === 'processed') {
                $this->orderStatusService->addSystemStatus($return->order, 'refund_completed', $adminId);
                $this->orderStatusService->addSystemStatus($return->order, 'return_closed', $adminId);
            }
        });
    }

    private function transitionByAdmin(int $returnId, int $adminId, string $fromStatus, string $toStatus, array $updates): void
    {
        DB::transaction(function () use ($returnId, $adminId, $fromStatus, $toStatus, $updates) {
            $return = ReturnRequest::query()
                ->with('order')
                ->where('id', $returnId)
                ->lockForUpdate()
                ->first();

            if (!$return) {
                throw new RuntimeException('Return request not found.');
            }

            if ($return->status !== $fromStatus) {
                throw new RuntimeException('Return request is not ready for this action.');
            }

            $return->fill(array_merge($updates, [
                'status' => $toStatus,
                'updated_by_id' => $adminId,
                'updated_date' => Carbon::now(),
            ]));
            $return->save();

            $this->orderStatusService->addSystemStatus($return->order, $toStatus, $adminId);
        });
    }

    private function getSuccessfulPayment(Order $order): ?Payment
    {
        return $order->payments
            ->whereIn('status', ['paid', 'refund_pending', 'partially_refunded', 'refunded'])
            ->sortByDesc('id')
            ->first();
    }

    public function getReturnableItems(Order $order)
    {
        $returnedQuantities = [];

        foreach ($order->returnRequests as $returnRequest) {
            if (in_array($returnRequest->status, ['return_rejected', 'inspection_failed'], true)) {
                continue;
            }

            foreach ($returnRequest->items as $returnItem) {
                $orderItemId = (int) $returnItem->order_item_id;
                $returnedQuantities[$orderItemId] = ($returnedQuantities[$orderItemId] ?? 0) + (int) $returnItem->quantity;
            }
        }

        return $order->items
            ->map(function ($item) use ($returnedQuantities) {
                $item->returnable_quantity = max(0, (int) $item->quantity - (int) ($returnedQuantities[(int) $item->id] ?? 0));

                return $item;
            })
            ->filter(function ($item) {
                return (int) $item->returnable_quantity > 0;
            })
            ->values();
    }

    private function buildSelectedReturnItems(Order $order, array $requestedItems, int $customerId)
    {
        $returnableItems = $this->getReturnableItems($order)->keyBy('id');
        $selectedItems = collect();

        foreach ($requestedItems as $orderItemId => $requestedItem) {
            $quantity = (int) ($requestedItem['quantity'] ?? 0);

            if ($quantity <= 0) {
                continue;
            }

            $orderItem = $returnableItems->get((int) $orderItemId);

            if (!$orderItem) {
                throw new RuntimeException('One or more selected items cannot be returned.');
            }

            if ($quantity > (int) $orderItem->returnable_quantity) {
                throw new RuntimeException('Return quantity cannot be more than purchased quantity.');
            }

            $unitPrice = (float) $orderItem->unit_price;
            $lineTotal = round($unitPrice * $quantity, 2);

            $selectedItems->push([
                'order_item_id' => (int) $orderItem->id,
                'product_id' => (int) $orderItem->product_id,
                'rate_master_id' => (int) $orderItem->rate_master_id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'created_by_id' => $customerId,
                'created_date' => Carbon::now(),
            ]);
        }

        return $selectedItems;
    }

    private function getDeliveredAt(Order $order): ?Carbon
    {
        if ($order->delivered_at) {
            return $order->delivered_at;
        }

        $deliveredStatus = $order->statuses
            ->where('order_status', OrderStatusService::STATUS_ORDER_DELIVERED)
            ->sortByDesc('action_time')
            ->first();

        if (!$deliveredStatus) {
            return null;
        }

        return $deliveredStatus->action_time;
    }

    private function restoreSellableStock(ReturnRequest $return, int $adminId): void
    {
        foreach ($return->items as $item) {
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
                'sale_order_id' => $return->order_id,
                'is_active' => 1,
                'created_by_id' => $adminId,
                'created_date' => Carbon::now(),
            ]);

            RateMaster::query()
                ->where('id', $item->rate_master_id)
                ->update([
                    'soldout_status' => $newStock > 0 ? 'NO' : 'YES',
                    'updated_by_id' => $adminId,
                    'updated_date' => Carbon::now(),
                ]);
        }
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
}
