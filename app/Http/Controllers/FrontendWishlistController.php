<?php

namespace App\Http\Controllers;

use App\Services\CustomerAuthService;
use App\Services\FrontendCatalogService;

class FrontendWishlistController extends Controller
{
    protected FrontendCatalogService $frontendCatalogService;
    protected CustomerAuthService $customerAuthService;

    public function __construct(FrontendCatalogService $frontendCatalogService, CustomerAuthService $customerAuthService)
    {
        $this->frontendCatalogService = $frontendCatalogService;
        $this->customerAuthService = $customerAuthService;
    }

    public function index()
    {
        if (!$this->customerAuthService->isCustomerLoggedIn()) {
            return redirect()
                ->route('frontend.login')
                ->with('error', 'Please login to view your wishlist.');
        }

        $menuCategories = $this->frontendCatalogService->getMenuCategories();

        return view('frontend.wishlist', compact('menuCategories'));
    }
}
