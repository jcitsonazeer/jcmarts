<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use Illuminate\Http\Request; 

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

public function index(Request $request)
{
    $subCategoryId = $request->sub_category_id;

    $products = $this->productService
        ->getActiveProductsForApi($subCategoryId);

    $products->getCollection()->transform(function ($product) {
        $product->product_image =
            asset('storage/products/' . $product->product_image);
        return $product;
    });

    return response()->json([
        'status' => true,
        'message' => 'Product list fetched successfully',
        'data' => $products
    ]);
}

}