<?php

namespace App\Services;

use App\Models\Category;
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

    public function getProducts($subCategoryId = null)
    {
        $query = Product::query()
            ->where('is_active', 1)
            ->whereHas('rates')
            ->when(!empty($subCategoryId), function ($query) use ($subCategoryId) {
                $query->where('subproduct_id', $subCategoryId);
            })
            ->with([
                'subCategory.category',
                'rates' => function ($query) {
                    $query->with('uom')
                        ->orderBy('id');
                },
            ])
            ->orderByDesc('id');

        if (empty($subCategoryId)) {
            $query->limit(12);
        }

        return $query->get();
    }
}
