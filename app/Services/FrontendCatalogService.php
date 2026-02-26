<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\RateMaster;
use App\Models\IndexBanner;

class FrontendCatalogService
{
    public function getMenuCategories()
    {
        return Category::with('subCategories')
            ->orderBy('category_name')
            ->get();
    }

    public function getTopSubCategories()
    {
        return SubCategory::with('category')
            ->orderByDesc('id')
            ->limit(12)
            ->get();
    }

    public function getIndexBanners()
    {
        return IndexBanner::query()
            ->orderByDesc('id')
            ->get();
    }

    public function getProductOffers()
    {
        $latestOfferRateIds = RateMaster::query()
            ->where('is_active', 1)
            ->where('offer_percentage', '>', 0)
            ->selectRaw('MAX(id) as id')
            ->groupBy('product_id');

        return RateMaster::query()
            ->joinSub($latestOfferRateIds, 'latest_offer_rates', function ($join) {
                $join->on('latest_offer_rates.id', '=', 'rate_master.id');
            })
            ->join('products', 'products.id', '=', 'rate_master.product_id')
            ->where('products.is_active', 1)
            ->where('rate_master.is_active', 1)
            ->select([
                'rate_master.product_id',
                'products.product_name',
                'products.product_image',
                'rate_master.offer_percentage',
                'rate_master.final_price',
                'rate_master.selling_price',
            ])
            ->orderByDesc('rate_master.id')
            ->orderByDesc('offer_percentage')
            ->limit(12)
            ->get();
    }

    public function getFeaturedProducts()
    {
        return Product::query()
            ->where('is_active', 1)
            ->whereHas('rates', function ($query) {
                $query->where('is_active', 1);
            })
            ->with([
                'rates' => function ($query) {
                    $query->where('is_active', 1)
                        ->with('uom')
                        ->orderBy('id');
                },
            ])
            ->orderByDesc('id')
            ->limit(12)
            ->get();
    }
}
