<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Wishlist;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ApiWishlistService
{
    /**
     * Add a product to the wishlist.
     * Re-activates if it was previously soft-deleted.
     */
    public function add(int $customerId, int $productId): Wishlist
    {
        $now = Carbon::now();

        // Validate product exists and is active
        Product::query()
            ->where('id', $productId)
            ->where('is_active', 1)
            ->firstOrFail();

        // Check if a wishlist record already exists for this customer + product
        $wishlist = Wishlist::query()
            ->where('customer_id', $customerId)
            ->where('product_id', $productId)
            ->first();

        if ($wishlist) {
            // If it was soft-deleted, re-activate it
            if ((int) $wishlist->is_active !== 1) {
                $wishlist->is_active = 1;
                $wishlist->updated_by_id = $customerId;
                $wishlist->updated_date = $now;
                $wishlist->save();
            }

            return $wishlist;
        }

        // Create new wishlist entry
        return Wishlist::create([
            'customer_id' => $customerId,
            'product_id' => $productId,
            'is_active' => 1,
            'created_by_id' => $customerId,
            'created_date' => $now,
        ]);
    }

    /**
     * Remove a product from the wishlist (soft delete).
     */
    public function remove(int $customerId, int $productId): void
    {
        $now = Carbon::now();

        $wishlist = Wishlist::query()
            ->where('customer_id', $customerId)
            ->where('product_id', $productId)
            ->where('is_active', 1)
            ->first();

        if (!$wishlist) {
            return;
        }

        $wishlist->is_active = 0;
        $wishlist->updated_by_id = $customerId;
        $wishlist->updated_date = $now;
        $wishlist->save();
    }

    /**
     * Toggle wishlist status for a product.
     * Returns true if added, false if removed.
     */
    public function toggle(int $customerId, int $productId): bool
    {
        if ($this->isInWishlist($customerId, $productId)) {
            $this->remove($customerId, $productId);
            return false;
        }

        $this->add($customerId, $productId);
        return true;
    }

    /**
     * Check if a product is in the customer's wishlist.
     */
    public function isInWishlist(int $customerId, int $productId): bool
    {
        return Wishlist::query()
            ->where('customer_id', $customerId)
            ->where('product_id', $productId)
            ->where('is_active', 1)
            ->exists();
    }

    /**
     * Get all active wishlist items for a customer with full product details.
     */
    public function getActiveItems(int $customerId): Collection
    {
        return Wishlist::query()
            ->select([
                'id',
                'customer_id',
                'product_id',
            ])
            ->where('customer_id', $customerId)
            ->where('is_active', 1)
            ->with([
                'product' => function ($query) {
                    $query->select([
                        'id',
                        'sub_category_id',
                        'brand_id',
                        'product_name',
                        'product_image',
                    ])->with([
                        'subCategory:id,category_id,sub_category_name',
                        'subCategory.category:id,category_name',
                        'brand:id,brand_name',
                        'rates' => function ($rateQuery) {
                            $rateQuery->select([
                                'id',
                                'product_id',
                                'uom_id',
                                'selling_price',
                                'offer_percentage',
                                'offer_price',
                                'final_price',
                                'soldout_status',
                                'selected_display',
                                'is_active',
                            ])
                                ->where('is_active', 1)
                                ->with(['uom:id,primary_uom,secondary_uom'])
                                ->orderBy('id');
                        },
                    ]);
                },
            ])
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Get total wishlist item count for a customer.
     */
    public function getItemCount(int $customerId): int
    {
        return (int) Wishlist::query()
            ->where('customer_id', $customerId)
            ->where('is_active', 1)
            ->count();
    }
}
