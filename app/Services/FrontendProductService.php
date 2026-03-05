<?php

namespace App\Services;

use App\Models\Category;
use App\Models\OfferDetail;
use App\Models\Product;
use App\Models\SubCategory;

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
        $query = Product::query()
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
            })
            ->with([
                'subCategory.category',
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
}
