<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\RateMaster;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ApiCartService
{
    /**
     * Build a session identifier for the cart.
     *
     * - Guest users:  "device_{uuid}"   (Flutter app generates a unique device ID)
     * - Auth users:   "customer_{id}"   (tied to the logged-in customer)
     */
    public function buildSessionId(?int $customerId, ?string $deviceId): string
    {
        if ($customerId) {
            return 'customer_' . $customerId;
        }

        return 'device_' . $deviceId;
    }

    /**
     * Add an item to the cart.
     */
    public function addItem(string $sessionId, int $productId, int $rateMasterId, int $quantity = 1): Cart
    {
        $quantity = max(1, $quantity);

        // Validate that the rate master belongs to the product and is active
        $rate = RateMaster::query()
            ->where('id', $rateMasterId)
            ->where('product_id', $productId)
            ->where('is_active', 1)
            ->firstOrFail();

        // Calculate unit price from the rate
        $unitPrice = (float) ($rate->final_price > 0 ? $rate->final_price : $rate->selling_price);

        // Check if this exact product + rate already exists in cart
        $cartItem = Cart::query()
            ->where('session_id', $sessionId)
            ->where('product_id', $productId)
            ->where('rate_master_id', $rateMasterId)
            ->where('is_active', 1)
            ->first();

        if ($cartItem) {
            // Item already exists, increase quantity
            $cartItem->quantity = (int) $cartItem->quantity + $quantity;
            $cartItem->unit_price = $unitPrice;
            $cartItem->updated_date = Carbon::now();
            $cartItem->save();

            return $cartItem;
        }

        // Create new cart item
        return Cart::create([
            'session_id' => $sessionId,
            'product_id' => $productId,
            'rate_master_id' => $rateMasterId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'is_active' => 1,
            'created_date' => Carbon::now(),
        ]);
    }

    /**
     * Get all active cart items.
     */
    public function getActiveItems(string $sessionId): Collection
    {
        return Cart::query()
            ->select([
                'id',
                'product_id',
                'rate_master_id',
                'quantity',
                'unit_price',
            ])
            ->where('session_id', $sessionId)
            ->where('is_active', 1)
            ->with([
                'product:id,product_name,product_image',
                'rate:id,uom_id,final_price,selling_price',
                'rate.uom:id,primary_uom,secondary_uom',
            ])
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Get total item count.
     */
    public function getItemCount(string $sessionId): int
    {
        return (int) Cart::query()
            ->where('session_id', $sessionId)
            ->where('is_active', 1)
            ->sum('quantity');
    }

    /**
     * Get the sub total for the cart.
     */
    public function getSubTotal(string $sessionId): float
    {
        return (float) Cart::query()
            ->where('session_id', $sessionId)
            ->where('is_active', 1)
            ->sum(\DB::raw('quantity * unit_price'));
    }

    /**
     * Update the quantity of a cart item.
     */
    public function updateQuantity(string $sessionId, int $cartId, int $quantity): void
    {
        $cartItem = Cart::query()
            ->where('id', $cartId)
            ->where('session_id', $sessionId)
            ->where('is_active', 1)
            ->firstOrFail();

        $cartItem->quantity = max(1, $quantity);
        $cartItem->updated_date = Carbon::now();
        $cartItem->save();
    }

    /**
     * Remove a cart item (soft delete).
     */
    public function removeItem(string $sessionId, int $cartId): void
    {
        $cartItem = Cart::query()
            ->where('id', $cartId)
            ->where('session_id', $sessionId)
            ->where('is_active', 1)
            ->firstOrFail();

        $cartItem->is_active = 0;
        $cartItem->updated_date = Carbon::now();
        $cartItem->save();
    }

    /**
     * Merge a guest (device) cart into an authenticated (customer) cart.
     *
     * - If the same product+rate exists in the customer cart, quantities are added.
     * - Guest cart items are soft-deleted after merge.
     *
     * Returns the merged customer cart items.
     */
    public function mergeGuestCart(string $guestSessionId, string $customerSessionId): Collection
    {
        $now = Carbon::now();

        // Get all active guest cart items
        $guestItems = Cart::query()
            ->where('session_id', $guestSessionId)
            ->where('is_active', 1)
            ->get();

        foreach ($guestItems as $guestItem) {
            // Check if customer already has this product+rate in their cart
            $existingItem = Cart::query()
                ->where('session_id', $customerSessionId)
                ->where('product_id', $guestItem->product_id)
                ->where('rate_master_id', $guestItem->rate_master_id)
                ->where('is_active', 1)
                ->first();

            if ($existingItem) {
                // Merge: add guest quantity to existing customer item
                $existingItem->quantity = (int) $existingItem->quantity + (int) $guestItem->quantity;
                $existingItem->updated_date = $now;
                $existingItem->save();
            } else {
                // Move: reassign guest item to customer session
                $guestItem->session_id = $customerSessionId;
                $guestItem->updated_date = $now;
                $guestItem->save();
            }

            // Soft-delete the original guest entry if it wasn't reassigned
            if (!$existingItem && $guestItem->session_id === $customerSessionId) {
                // Already reassigned above, nothing more to do
            } elseif ($existingItem) {
                // Soft-delete the guest item since we merged into existing
                $guestItem->is_active = 0;
                $guestItem->updated_date = $now;
                $guestItem->save();
            }
        }

        return $this->getActiveItems($customerSessionId);
    }

    /**
     * Clear all cart items for a session.
     */
    public function clearCart(string $sessionId): void
    {
        $now = Carbon::now();

        Cart::query()
            ->where('session_id', $sessionId)
            ->where('is_active', 1)
            ->update([
                'is_active' => 0,
                'updated_date' => $now,
            ]);
    }
}
