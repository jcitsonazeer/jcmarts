<?php

namespace App\Services;

use App\Models\Order;

class FrontendOrderService
{
    protected OrderStatusService $orderStatusService;
    protected ReturnService $returnService;

    public function __construct(OrderStatusService $orderStatusService, ReturnService $returnService)
    {
        $this->orderStatusService = $orderStatusService;
        $this->returnService = $returnService;
    }

    public function getOrdersForCustomer(int $customerId, ?string $search = null)
    {
        $query = Order::query()
            ->where('customer_id', $customerId)
            ->where('is_active', 1);

        $search = trim((string) $search);

        if ($search !== '' && ctype_digit($search)) {
            $query->where('id', (int) $search);
        } elseif ($search !== '') {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->whereHas('payments', function ($paymentQuery) use ($search) {
                    $paymentQuery->where('status', 'like', '%' . $search . '%')
                        ->orWhere('gateway', 'like', '%' . $search . '%');
                });

                $subQuery->orWhereHas('statuses', function ($statusQuery) use ($search) {
                    $statusQuery->where('order_status', 'like', '%' . $search . '%');
                });
            });
        }

        $orders = $query
            ->with(['statuses', 'payments', 'returnRequests.items'])
            ->withCount('items')
            ->orderByDesc('created_date')
            ->orderByDesc('id')
            ->get();

        return $orders->each(function (Order $order) {
            $latestStatus = $this->orderStatusService->getLatestStatusForOrder($order);
            $order->current_order_status = $latestStatus ? $latestStatus->order_status : null;

            $this->attachCurrentPaymentDetails($order);
        });
    }

    public function getOrderForCustomer(int $orderId, int $customerId): ?Order
    {
        $order = Order::query()
            ->where('id', $orderId)
            ->where('customer_id', $customerId)
            ->with([
                'customer',
                'items.product',
                'items.rate',
                'address',
                'statuses',
                'payments',
                'refunds',
                'returnRequests.items.product',
                'returnRequests.refunds',
            ])
            ->first();

        if ($order) {
            $latestStatus = $this->orderStatusService->getLatestStatusForOrder($order);
            $order->current_order_status = $latestStatus ? $latestStatus->order_status : null;
            $order->order_status_timeline = $this->orderStatusService->buildTimeline($order->statuses, $order);
            $order->can_customer_cancel = $order->is_active
                && $this->orderStatusService->canCustomerCancel($order->current_order_status);
            $order->can_customer_return = $this->returnService->canCustomerRequestReturn($order);
            $order->return_allowed_until = $this->returnService->returnAllowedUntil($order);
            $order->return_period_expired = $this->returnService->isReturnPeriodExpired($order);
            $order->returnable_items = $this->returnService->getReturnableItems($order);
            $this->attachCurrentPaymentDetails($order);
        }

        return $order;
    }

    private function attachCurrentPaymentDetails(Order $order): void
    {
        $latestPayment = $order->payments->sortByDesc('id')->first();

        $order->current_payment_method = null;
        $order->current_payment_status = null;
        $order->current_payment_paid_at = null;

        if ($latestPayment) {
            $order->current_payment_method = $latestPayment->gateway;
            $order->current_payment_status = $latestPayment->status;
            $order->current_payment_paid_at = $latestPayment->paid_at;
        }
    }
}
