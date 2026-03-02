<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\RateMaster;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CartService
{
    public function getCurrentSessionId(): string
    {
        if (!session()->isStarted()) {
            session()->start();
        }

        return session()->getId();
    }

    public function addItem(int $productId, int $rateMasterId, int $quantity = 1, ?int $createdById = null): Cart
    {
        $sessionId = $this->getCurrentSessionId();
        $quantity = max(1, $quantity);

        $rate = RateMaster::query()
            ->where('id', $rateMasterId)
            ->where('product_id', $productId)
            ->where('is_active', 1)
            ->firstOrFail();

        $unitPrice = (float) ($rate->final_price > 0 ? $rate->final_price : $rate->selling_price);

        $cartItem = Cart::query()
            ->where('session_id', $sessionId)
            ->where('product_id', $productId)
            ->where('rate_master_id', $rateMasterId)
            ->where('is_active', 1)
            ->first();

        if ($cartItem) {
            $cartItem->quantity = (int) $cartItem->quantity + $quantity;
            $cartItem->unit_price = $unitPrice;
            $cartItem->updated_by_id = $createdById;
            $cartItem->updated_date = Carbon::now();
            $cartItem->save();

            return $cartItem;
        }

        return Cart::create([
            'session_id' => $sessionId,
            'product_id' => $productId,
            'rate_master_id' => $rateMasterId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'is_active' => 1,
            'created_by_id' => $createdById,
            'created_date' => Carbon::now(),
        ]);
    }

    public function getItemCount(): int
    {
        $sessionId = $this->getCurrentSessionId();

        return (int) Cart::query()
            ->where('session_id', $sessionId)
            ->where('is_active', 1)
            ->sum('quantity');
    }

    public function getActiveItems(): Collection
    {
        $sessionId = $this->getCurrentSessionId();

        return Cart::query()
            ->where('session_id', $sessionId)
            ->where('is_active', 1)
            ->with([
                'product',
                'rate.uom',
            ])
            ->orderByDesc('id')
            ->get();
    }

    public function updateQuantity(int $cartId, int $quantity): void
    {
        $sessionId = $this->getCurrentSessionId();

        $cartItem = Cart::query()
            ->where('id', $cartId)
            ->where('session_id', $sessionId)
            ->where('is_active', 1)
            ->firstOrFail();

        $cartItem->quantity = max(1, $quantity);
        $cartItem->updated_date = Carbon::now();
        $cartItem->save();
    }

    public function removeItem(int $cartId): void
    {
        $sessionId = $this->getCurrentSessionId();

        $cartItem = Cart::query()
            ->where('id', $cartId)
            ->where('session_id', $sessionId)
            ->where('is_active', 1)
            ->firstOrFail();

        $cartItem->is_active = 0;
        $cartItem->updated_date = Carbon::now();
        $cartItem->save();
    }
}
