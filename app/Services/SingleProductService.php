<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SingleProductService
{
    public function getMenuCategories()
    {
        return Category::with('subCategories')
            ->orderBy('category_name')
            ->get();
    }

    public function getProduct(?int $productId = null)
    {
        $query = Product::query()
            ->where('is_active', 1)
            ->whereHas('rates', function ($query) {
                $query->where('is_active', 1);
            })
            ->with([
                'subCategory.category',
                'rates' => function ($query) {
                    $query->where('is_active', 1)->with('uom')->orderBy('id');
                },
            ]);

        if (!empty($productId)) {
            $product = $query->where('id', $productId)->first();
            if (!$product) {
                throw new ModelNotFoundException('Product not found');
            }

            return $product;
        }

        $product = $query->orderByDesc('id')->first();
        if (!$product) {
            throw new ModelNotFoundException('No active product found');
        }

        return $product;
    }

    public function getGalleryImages(Product $product): array
    {
        $images = [];

        if (!empty($product->single_image_1)) {
            $images[] = $product->single_image_1;
        }
        if (!empty($product->single_image_2)) {
            $images[] = $product->single_image_2;
        }
        if (!empty($product->single_image_3)) {
            $images[] = $product->single_image_3;
        }
        if (!empty($product->single_image_4)) {
            $images[] = $product->single_image_4;
        }

        return $images;
    }

    public function getRelatedProducts(Product $product, int $limit = 10)
    {
        return Product::query()
            ->where('is_active', 1)
            ->where('sub_category_id', $product->sub_category_id)
            ->where('id', '!=', $product->id)
            ->whereHas('rates', function ($query) {
                $query->where('is_active', 1);
            })
            ->with([
                'rates' => function ($query) {
                    $query->where('is_active', 1)->with('uom')->orderBy('id');
                },
            ])
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

}
