<?php

namespace App\Http\Controllers;

use App\Services\FrontendProductService;
use Illuminate\Http\Request;

class FrontendProductController extends Controller
{
    protected $frontendProductService;

    public function __construct(FrontendProductService $frontendProductService)
    {
        $this->frontendProductService = $frontendProductService;
    }

    public function index(Request $request)
    {
        $subCategoryId = $request->query('sub_category');
        $offerId = $request->query('offer');
        $searchTerm = trim((string) $request->query('search', ''));
        $searchTerm = $searchTerm !== '' ? $searchTerm : null;
        $selectedBrandIds = collect((array) $request->query('brands', []))
            ->filter(function ($brandId) {
                return is_numeric($brandId);
            })
            ->map(function ($brandId) {
                return (int) $brandId;
            })
            ->unique()
            ->values()
            ->all();

        $selectedSubCategory = $this->frontendProductService->getSelectedSubCategory($subCategoryId);
        $selectedOffer = $this->frontendProductService->getSelectedOffer($offerId);
        $selectedSubCategoryId = null;
        $selectedOfferId = null;

        if ($selectedSubCategory) {
            $selectedSubCategoryId = $selectedSubCategory->id;
        }

        if ($selectedOffer) {
            $selectedOfferId = $selectedOffer->id;
        }

        $menuCategories = $this->frontendProductService->getMenuCategories();
        $availableBrands = $this->frontendProductService->getAvailableBrands($selectedSubCategoryId, $searchTerm, $selectedOfferId);
        $products = $this->frontendProductService->getProductsByBrands(
            $selectedSubCategoryId,
            $searchTerm,
            $selectedOfferId,
            $selectedBrandIds
        );

        return view('frontend.products', compact(
            'menuCategories',
            'products',
            'selectedSubCategory',
            'selectedOffer',
            'searchTerm',
            'availableBrands',
            'selectedBrandIds'
        ));
    }
}
