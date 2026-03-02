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
        return $this->buildProductResponse($request, 'Product list fetched successfully');
    }

    public function search(Request $request)
    {
        return $this->buildProductResponse($request, 'Product search fetched successfully');
    }

    private function buildProductResponse(Request $request, string $message)
    {
        $subCategoryId = $request->query('sub_category_id');
        $search = trim((string) $request->query('search', ''));
        $search = $search !== '' ? $search : null;
        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min($perPage, 100));

        $products = $this->productService->getActiveProductsForApi($subCategoryId, $search, $perPage);

        $products->getCollection()->transform(function ($product) {
            $product->product_image = $product->product_image
                ? asset('storage/product/' . $product->product_image)
                : null;

            return $product;
        });

        return response()->json([
            'status' => true,
            'message' => $message,
            'filters' => [
                'sub_category_id' => $subCategoryId,
                'search' => $search,
                'per_page' => $perPage,
            ],
            'data' => $products,
        ]);
    }

}
