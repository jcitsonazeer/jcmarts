<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Wishlist;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class WishlistService
{
    public function getCustomerId(): ?int
    {
        $customerId = session('customer_id');
        return $customerId ? (int) $customerId : null;
    }

    public function getItemCount(?int $customerId = null): int
    {
        $customerId = $customerId ?? $this->getCustomerId();
        if (!$customerId) {
            return 0;
        }

        return (int) Wishlist::query()
            ->where('customer_id', $customerId)
            ->where('is_active', 1)
            ->count();
    }

    public function getActiveItems(int $customerId): Collection
    {
        return Wishlist::query()
            ->select([
                'id',
                'customer_id',
                'product_id',
                'is_active',
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
                        'is_active',
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
                            ])->where('is_active', 1)
                                ->with(['uom:id,primary_uom,secondary_uom'])
                                ->orderBy('id');
                        },
                    ]);
                },
            ])
            ->orderByDesc('id')
            ->get();
    }

    public function isInWishlist(int $customerId, int $productId): bool
    {
        return Wishlist::query()
            ->where('customer_id', $customerId)
            ->where('product_id', $productId)
            ->where('is_active', 1)
            ->exists();
    }

    public function add(int $customerId, int $productId): Wishlist
    {
        $now = Carbon::now();

        Product::query()
            ->where('id', $productId)
            ->where('is_active', 1)
            ->firstOrFail();

        $wishlist = Wishlist::query()
            ->where('customer_id', $customerId)
            ->where('product_id', $productId)
            ->first();

        if ($wishlist) {
            if ((int) $wishlist->is_active !== 1) {
                $wishlist->is_active = 1;
                $wishlist->updated_by_id = $customerId;
                $wishlist->updated_date = $now;
                $wishlist->save();
            }

            return $wishlist;
        }

        return Wishlist::create([
            'customer_id' => $customerId,
            'product_id' => $productId,
            'is_active' => 1,
            'created_by_id' => $customerId,
            'created_date' => $now,
        ]);
    }

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

    public function toggle(int $customerId, int $productId): bool
    {
        if ($this->isInWishlist($customerId, $productId)) {
            $this->remove($customerId, $productId);
            return false;
        }

        $this->add($customerId, $productId);
        return true;
    }
}
