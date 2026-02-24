<?php

namespace App\Http\Controllers;

use App\Services\SingleProductService;
use Illuminate\Http\Request;

class SingleProductController extends Controller
{
    protected $singleProductService;

    public function __construct(SingleProductService $singleProductService)
    {
        $this->singleProductService = $singleProductService;
    }

    public function show(Request $request)
    {
        $productId = $request->query('product_id');
        $productId = is_numeric($productId) ? (int) $productId : null;

        $menuCategories = $this->singleProductService->getMenuCategories();
        $product = $this->singleProductService->getProduct($productId);
        $galleryImages = $this->singleProductService->getGalleryImages($product);
        $relatedProducts = $this->singleProductService->getRelatedProducts($product);

        return view('frontend.single_product', compact(
            'menuCategories',
            'product',
            'galleryImages',
            'relatedProducts'
        ));
    }
}
