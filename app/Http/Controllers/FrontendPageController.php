<?php

namespace App\Http\Controllers;

use App\Services\FrontendCatalogService;

class FrontendPageController extends Controller
{
    protected FrontendCatalogService $frontendCatalogService;

    public function __construct(FrontendCatalogService $frontendCatalogService)
    {
        $this->frontendCatalogService = $frontendCatalogService;
    }

    protected function renderPage(string $view)
    {
        $menuCategories = $this->frontendCatalogService->getMenuCategories();

        return view($view, compact('menuCategories'));
    }

    public function aboutUs()
    {
        return $this->renderPage('frontend.about_us');
    }

    public function deliveryInfo()
    {
        return $this->renderPage('frontend.delivery_info');
    }

    public function privacyPolicy()
    {
        return $this->renderPage('frontend.privacy_policy');
    }

    public function termsConditions()
    {
        return $this->renderPage('frontend.terms_conditions');
    }

    public function returns()
    {
        return $this->renderPage('frontend.returns');
    }
}
