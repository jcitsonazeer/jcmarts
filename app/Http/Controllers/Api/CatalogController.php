<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FrontendCatalogService;

class CatalogController extends Controller
{
    public function __construct(protected FrontendCatalogService $frontendCatalogService)
    {
    }

    public function home()
    {
        $productCategories = $this->frontendCatalogService->getTopSubCategories();
        $productOffers = $this->frontendCatalogService->getProductOffers();
        $featuredProducts = $this->frontendCatalogService->getFeaturedProducts();

        $productCategories->transform(function ($category) {
            $category->sub_category_image = $category->sub_category_image
                ? asset('storage/sub_category/' . $category->sub_category_image)
                : null;

            return $category;
        });

        $productOffers->transform(function ($offer) {
            $offer->product_image = $offer->product_image
                ? asset('storage/product/' . $offer->product_image)
                : null;

            return $offer;
        });

        $featuredProducts->transform(function ($product) {
            $product->product_image = $product->product_image
                ? asset('storage/product/' . $product->product_image)
                : null;

            return $product;
        });

        return response()->json([
            'status' => true,
            'message' => 'Home page data fetched successfully',
            'data' => [
                'product_categories' => $productCategories,
                'product_offers' => $productOffers,
                'featured_products' => $featuredProducts,
            ],
        ]);
    }

    public function banners()
    {
        $banners = $this->frontendCatalogService->getIndexBanners();

        $banners->transform(function ($banner) {
            $banner->banner_image = $banner->banner_image
                ? asset('storage/index_banner/' . $banner->banner_image)
                : null;

            return $banner;
        });

        return response()->json([
            'status' => true,
            'message' => 'Banners fetched successfully',
            'data' => $banners,
        ]);
    }

    public function featuredProducts()
    {
        $products = $this->frontendCatalogService->getFeaturedProducts();

        $products->transform(function ($product) {
            $product->product_image = $product->product_image
                ? asset('storage/product/' . $product->product_image)
                : null;

            return $product;
        });

        return response()->json([
            'status' => true,
            'message' => 'Featured products fetched successfully',
            'data' => $products,
        ]);
    }

    public function offers()
    {
        $offers = $this->frontendCatalogService->getProductOffers();

        $offers->transform(function ($offer) {
            $offer->product_image = $offer->product_image
                ? asset('storage/product/' . $offer->product_image)
                : null;

            return $offer;
        });

        return response()->json([
            'status' => true,
            'message' => 'Offer products fetched successfully',
            'data' => $offers,
        ]);
    }
}
