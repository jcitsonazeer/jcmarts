<?php

namespace App\Services;

use App\Models\Order;

class FrontendOrderService
{
    public function getOrdersForCustomer(int $customerId)
    {
        return Order::query()
            ->where('customer_id', $customerId)
            ->where('is_active', 1)
            ->withCount('items')
            ->orderByDesc('created_date')
            ->orderByDesc('id')
            ->get();
    }

    public function getOrderForCustomer(int $orderId, int $customerId): ?Order
    {
        return Order::query()
            ->where('id', $orderId)
            ->where('customer_id', $customerId)
            ->with([
                'items.product',
                'items.rate',
                'address',
            ])
            ->first();
    }
}
