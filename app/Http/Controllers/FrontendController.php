<?php

namespace App\Http\Controllers;

use App\Services\FrontendCatalogService;

class FrontendController extends Controller
{
    protected $frontendCatalogService;

    public function __construct(FrontendCatalogService $frontendCatalogService)
    {
        $this->frontendCatalogService = $frontendCatalogService;
    }

    public function index()
    {
        $menuCategories = $this->frontendCatalogService->getMenuCategories();
        $indexBanners = $this->frontendCatalogService->getIndexBanners();
        $topSubCategories = $this->frontendCatalogService->getTopSubCategories();
        $productOffers = $this->frontendCatalogService->getProductOffers();
        $featuredProducts = $this->frontendCatalogService->getFeaturedProducts();

        return view('frontend.index', compact('menuCategories', 'indexBanners', 'topSubCategories', 'productOffers', 'featuredProducts'));
    }

    public function products()
    {
        $menuCategories = $this->frontendCatalogService->getMenuCategories();

        return view('frontend.products', compact('menuCategories'));
    }

    public function single_product()
    {
        $menuCategories = $this->frontendCatalogService->getMenuCategories();

        return view('frontend.single_product', compact('menuCategories'));
    }

    public function cart()
    {
        $menuCategories = $this->frontendCatalogService->getMenuCategories();

        return view('frontend.cart', compact('menuCategories'));
    }

    public function checkout()
    {
        $menuCategories = $this->frontendCatalogService->getMenuCategories();

        return view('frontend.checkout', compact('menuCategories'));
    }
}
