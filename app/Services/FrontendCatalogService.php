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
        return Category::query()
            ->select(['id', 'category_name'])
            ->has('subCategories')
            ->with(['subCategories' => function ($query) {
                $query->select(['id', 'category_id', 'sub_category_name'])
                    ->orderBy('sub_category_name');
            }])
            ->orderBy('category_name')
            ->get();
    }

    public function getTopSubCategories()
    {
        return SubCategory::query()
            ->select(['id', 'category_id', 'sub_category_name', 'sub_category_image'])
            ->with(['category:id,category_name'])
            ->orderByDesc('id')
            ->limit(12)
            ->get();
    }

    public function getIndexBanners()
    {
        return IndexBanner::query()
            ->select(['id', 'banner_image', 'sub_category_id', 'offer_details_id'])
            ->with([
                'subCategory:id,sub_category_name',
                'offerDetail:id,offer_name',
            ])
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
            ->select([
                'id',
                'brand_id',
                'product_name',
                'product_image',
                'is_active',
            ])
            ->where('is_active', 1)
            ->whereHas('rates', function ($query) {
                $query->where('is_active', 1);
            })
            ->with([
                'brand:id,brand_name',
                'rates' => function ($query) {
                    $query->select([
                        'id',
                        'product_id',
                        'uom_id',
                        'selling_price',
                        'offer_percentage',
                        'offer_price',
                        'final_price',
                        'soldout_status',
                        'stock_dependent',
                        'is_active',
                        'selected_display',
                    ])
                        ->where('is_active', 1)
                        ->with(['uom:id,primary_uom,secondary_uom'])
                        ->orderByDesc('selected_display')
                        ->orderBy('id');
                },
            ])
            ->orderByDesc('id')
            ->limit(12)
            ->get();
    }
}
