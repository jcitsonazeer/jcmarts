<?php

namespace App\Services;

use App\Models\Order;

class AdminOrderService
{
    public function getAllOrders()
    {
        return Order::query()
            ->with(['customer'])
            ->withCount('items')
            ->orderByDesc('created_date')
            ->orderByDesc('id')
            ->get();
    }

    public function getOrderById(int $orderId): ?Order
    {
        return Order::query()
            ->with([
                'customer',
                'address',
                'items.product',
                'items.rate',
            ])
            ->where('id', $orderId)
            ->first();
    }
}
