<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Collection;

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
            ->with(['customer'])
            ->with(['statuses'])
            ->withCount('items')
            ->orderByDesc('created_date')
            ->orderByDesc('id')
            ->get();

        return $orders->each(function (Order $order) {
            $order->current_order_status = $this->orderStatusService->getLatestStatusForOrder($order)?->order_status;
        });
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

    public function releaseExpiredPendingOrder(int $orderId, int $adminId): void
    {
        $order = $this->orderService->getExpiredPendingOrders()
            ->firstWhere('id', $orderId);

        if (!$order) {
            return;
        }

        $this->orderService->releasePendingOrder($orderId, null, $adminId);
    }
}

