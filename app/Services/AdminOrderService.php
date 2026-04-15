<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminOrderService
{
    protected OrderStatusService $orderStatusService;
    protected OrderService $orderService;

    public function __construct(OrderStatusService $orderStatusService, OrderService $orderService)
    {
        $this->orderStatusService = $orderStatusService;
        $this->orderService = $orderService;
    }

    public function getAllOrders()
    {
        $orders = Order::query()
            ->select([
                'id',
                'customer_id',
                'total_amount',
                'currency',
                'payment_status',
                'created_date',
                'paid_at',
            ])
            ->with(['customer'])
            ->with(['statuses'])
            ->withCount('items')
            ->orderByDesc('created_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $orders->getCollection()->transform(function (Order $order) {
            $order->current_order_status = $this->orderStatusService->getLatestStatusForOrder($order)?->order_status;

            return $order;
        });

        return $orders;
    }

    public function getOrderById(int $orderId): ?Order
    {
        $order = Order::query()
            ->with([
                'customer',
                'address',
                'items.product',
                'items.rate',
                'statuses',
            ])
            ->where('id', $orderId)
            ->first();

        if ($order) {
            $order->current_order_status = $this->orderStatusService->getLatestStatusForOrder($order)?->order_status;
            $order->order_status_timeline = $this->orderStatusService->buildTimeline($order->statuses, $order);
        }

        return $order;
    }

    public function getExpiredPendingOrders(): Collection
    {
        return $this->orderService->getExpiredPendingOrders();
    }

    public function cleanupExpiredPendingOrders(): void
    {
        $this->orderService->cleanupExpiredPendingOrders();
    }

    public function getReleasedReservationHistory(): LengthAwarePaginator
    {
        return $this->orderService->getReleasedReservationHistory();
    }

    public function releaseExpiredPendingOrder(int $orderId, int $adminId): void
    {
        $order = $this->orderService->getExpiredPendingOrders()
            ->firstWhere('id', $orderId);

        if (!$order) {
            return;
        }

        $this->orderService->releasePendingOrderAsAdmin($orderId, $adminId);
    }
}
