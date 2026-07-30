<?php

namespace App\Services;

use App\Models\Order;

class FrontendOrderService
{
    protected OrderStatusService $orderStatusService;

    public function __construct(OrderStatusService $orderStatusService)
    {
        $this->orderStatusService = $orderStatusService;
    }

    public function getOrdersForCustomer(int $customerId, ?string $search = null)
    {
        $orders = Order::query()
            ->where('customer_id', $customerId)
            ->where('is_active', 1)
            ->when($search, function ($query) use ($search) {
                $search = trim((string) $search);
                if ($search === '') {
                    return $query;
                }

                if (ctype_digit($search)) {
                    return $query->where('id', (int) $search);
                }

                return $query->where(function ($subQuery) use ($search) {
                    $subQuery->whereHas('payments', function ($paymentQuery) use ($search) {
                        $paymentQuery->where('status', 'like', '%' . $search . '%')
                            ->orWhere('gateway', 'like', '%' . $search . '%');
                    })->orWhereHas('statuses', function ($statusQuery) use ($search) {
                        $statusQuery->where('order_status', 'like', '%' . $search . '%');
                    });
                });
            })
            ->with(['statuses', 'payments'])
            ->withCount('items')
            ->orderByDesc('created_date')
            ->orderByDesc('id')
            ->get();

        return $orders->each(function (Order $order) {
            $order->current_order_status = $this->orderStatusService->getLatestStatusForOrder($order)?->order_status;
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
            ])
            ->first();

        if ($order) {
            $order->current_order_status = $this->orderStatusService->getLatestStatusForOrder($order)?->order_status;
            $order->order_status_timeline = $this->orderStatusService->buildTimeline($order->statuses, $order);
            $order->can_customer_cancel = $order->is_active
                && $this->orderStatusService->canCustomerCancel($order->current_order_status);
            $this->attachCurrentPaymentDetails($order);
        }

        return $order;
    }

    private function attachCurrentPaymentDetails(Order $order): void
    {
        $latestPayment = $order->payments->sortByDesc('id')->first();

        $order->current_payment_method = $latestPayment?->gateway;
        $order->current_payment_status = $latestPayment?->status;
        $order->current_payment_paid_at = $latestPayment?->paid_at;
    }
}
