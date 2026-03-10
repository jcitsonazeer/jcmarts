<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\OfferDetail;
use App\Models\Product;
use App\Models\SubCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FrontendProductService
{
    public function getMenuCategories()
    {
        return Category::with('subCategories')
            ->orderBy('category_name')
            ->get();
    }

    public function getSelectedSubCategory($subCategoryId)
    {
        if (empty($subCategoryId) || !is_numeric($subCategoryId)) {
            return null;
        }

        return SubCategory::with('category')->find($subCategoryId);
    }

    public function getSelectedOffer($offerId)
    {
        if (empty($offerId) || !is_numeric($offerId)) {
            return null;
        }

        return OfferDetail::query()
            ->where('is_active', 1)
            ->find($offerId);
    }

    public function getProducts($subCategoryId = null, $searchTerm = null, $offerId = null)
    {
        $query = $this->buildBaseProductsQuery($subCategoryId, $searchTerm, $offerId)
            ->with([
                'subCategory.category',
                'brand',
                'rates' => function ($query) {
                    $query->where('is_active', 1)
                        ->with('uom')
                        ->orderBy('id');
                },
            ])
            ->orderByDesc('id');

        if (empty($subCategoryId) && empty($searchTerm) && empty($offerId)) {
            $query->limit(12);
        }

        return $query->get();
    }

    public function getProductsByBrands($subCategoryId = null, $searchTerm = null, $offerId = null, array $brandIds = [])
    {
        $query = $this->buildBaseProductsQuery($subCategoryId, $searchTerm, $offerId)
            ->when(!empty($brandIds), function (Builder $query) use ($brandIds) {
                $query->whereIn('brand_id', $brandIds);
            })
            ->with([
                'subCategory.category',
                'brand',
                'rates' => function ($query) {
                    $query->where('is_active', 1)
                        ->with('uom')
                        ->orderBy('id');
                },
            ])
            ->orderByDesc('id');

        if (empty($subCategoryId) && empty($searchTerm) && empty($offerId) && empty($brandIds)) {
            $query->limit(12);
        }

        return $query->get();
    }

    public function getAvailableBrands($subCategoryId = null, $searchTerm = null, $offerId = null): Collection
    {
        $brandProductCounts = $this->buildBaseProductsQuery($subCategoryId, $searchTerm, $offerId)
            ->whereNotNull('brand_id')
            ->selectRaw('brand_id, COUNT(*) as product_count')
            ->groupBy('brand_id')
            ->pluck('product_count', 'brand_id');

        if ($brandProductCounts->isEmpty()) {
            return collect();
        }

        return Brand::query()
            ->where('is_active', 1)
            ->whereIn('id', $brandProductCounts->keys())
            ->orderBy('brand_name')
            ->get()
            ->map(function (Brand $brand) use ($brandProductCounts) {
                $brand->product_count = (int) ($brandProductCounts[$brand->id] ?? 0);
                return $brand;
            })
            ->filter(function (Brand $brand) {
                return $brand->product_count > 0;
            })
            ->values();
    }

    private function buildBaseProductsQuery($subCategoryId = null, $searchTerm = null, $offerId = null): Builder
    {
        return Product::query()
            ->where('is_active', 1)
            ->whereHas('rates', function ($query) {
                $query->where('is_active', 1);
            })
            ->when(!empty($offerId), function ($query) use ($offerId) {
                $query->whereHas('offerProducts', function ($offerProductQuery) use ($offerId) {
                    $offerProductQuery
                        ->where('offer_id', $offerId)
                        ->where('is_active', 1);
                });
            })
            ->when(!empty($subCategoryId), function ($query) use ($subCategoryId) {
                $query->where('sub_category_id', $subCategoryId);
            })
            ->when(!empty($searchTerm), function ($query) use ($searchTerm) {
                $query->where('product_name', 'like', '%' . $searchTerm . '%');
            });
    }
}
