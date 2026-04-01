<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::query()
            ->with(['subCategories' => function ($query) {
                $query->orderBy('sub_category_name');
            }])
            ->orderBy('category_name')
            ->get();

        $categories->each(function ($category) {
            $category->subCategories->transform(function ($subCategory) {
                $subCategory->sub_category_image = $subCategory->sub_category_image
                    ? asset('storage/sub_category/' . $subCategory->sub_category_image)
                    : null;

                return $subCategory;
            });

            return $category;
        });

        return response()->json([
            'status' => true,
            'message' => 'Categories fetched successfully',
            'data' => $categories,
        ]);
    }
}
