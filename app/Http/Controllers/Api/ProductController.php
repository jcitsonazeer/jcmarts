<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProductService;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

public function index()
{
    $products = $this->productService->getActiveProductsForApi();

    $products->transform(function ($product) {
        $product->product_image = asset('storage/products/' . $product->product_image);
        return $product;
    });

    return response()->json([
        'status' => true,
        'message' => 'Product list fetched successfully',
        'data' => $products
    ]);
}

}