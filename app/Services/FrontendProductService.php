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

    public function getSelectedSubCategory($subCategoryId)
    {
        if (empty($subCategoryId) || !is_numeric($subCategoryId)) {
            return null;
        }

        return SubCategory::query()
            ->select(['id', 'category_id', 'sub_category_name'])
            ->with(['category:id,category_name'])
            ->find($subCategoryId);
    }

    public function getSelectedOffer($offerId)
    {
        if (empty($offerId) || !is_numeric($offerId)) {
            return null;
        }

        return OfferDetail::query()
            ->select(['id', 'offer_name'])
            ->where('is_active', 1)
            ->find($offerId);
    }

    public function getProducts($subCategoryId = null, $searchTerm = null, $offerId = null)
    {
        $query = $this->buildBaseProductsQuery($subCategoryId, $searchTerm, $offerId)
            ->with([
                'subCategory:id,category_id,sub_category_name',
                'subCategory.category:id,category_name',
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
            ->orderByDesc('id');

        return $query->paginate(12)->withQueryString();
    }

    public function getProductsByBrands($subCategoryId = null, $searchTerm = null, $offerId = null, array $brandIds = [])
    {
        $query = $this->buildBaseProductsQuery($subCategoryId, $searchTerm, $offerId)
            ->when(!empty($brandIds), function (Builder $query) use ($brandIds) {
                $query->whereIn('brand_id', $brandIds);
            })
            ->with([
                'subCategory:id,category_id,sub_category_name',
                'subCategory.category:id,category_name',
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
            ->orderByDesc('id');

        return $query->paginate(12)->withQueryString();
    }

    public function getAvailableBrands($subCategoryId = null, $searchTerm = null, $offerId = null): Collection
    {
        $brandProductCounts = Product::query()
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
            ->when(!empty(trim((string) $searchTerm)), function ($query) use ($searchTerm) {
                $query->where('product_name', 'like', trim((string) $searchTerm) . '%');
            })
            ->whereNotNull('brand_id')
            ->selectRaw('brand_id, COUNT(*) as product_count')
            ->groupBy('brand_id')
            ->pluck('product_count', 'brand_id');

        if ($brandProductCounts->isEmpty()) {
            return collect();
        }

        return Brand::query()
            ->select(['id', 'brand_name'])
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
        $normalizedSearchTerm = trim((string) $searchTerm);

        return Product::query()
            ->select([
                'id',
                'sub_category_id',
                'brand_id',
                'product_name',
                'product_image',
                'description',
                'warranty_info',
                'is_active',
            ])
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
            ->when($normalizedSearchTerm !== '', function ($query) use ($normalizedSearchTerm) {
                $query->where('product_name', 'like', $normalizedSearchTerm . '%');
            });
    }
}
