<?php

namespace App\Services;

use App\Models\Order;

class FrontendOrderService
{
    public function getOrdersForCustomer(int $customerId, ?string $search = null)
    {
        return Order::query()
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

                return $query->where('payment_status', 'like', '%' . $search . '%');
            })
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
