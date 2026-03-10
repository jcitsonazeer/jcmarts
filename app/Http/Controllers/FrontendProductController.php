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

        $menuCategories = $this->frontendProductService->getMenuCategories();
        $availableBrands = $this->frontendProductService->getAvailableBrands($selectedSubCategory?->id, $searchTerm, $selectedOffer?->id);
        $products = $this->frontendProductService->getProductsByBrands(
            $selectedSubCategory?->id,
            $searchTerm,
            $selectedOffer?->id,
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
