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
        $promoTileId = $request->query('promo_tile');
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
        $selectedPromoTile = $this->frontendProductService->getSelectedPromoTile($promoTileId);
        $selectedSubCategoryId = null;
        $selectedOfferId = null;
        $selectedPromoTileId = null;

        if ($selectedSubCategory) {
            $selectedSubCategoryId = $selectedSubCategory->id;
        }

        if ($selectedOffer) {
            $selectedOfferId = $selectedOffer->id;
        }

        if ($selectedPromoTile) {
            $selectedPromoTileId = (int) $selectedPromoTile->id;
        }

        $menuCategories = $this->frontendProductService->getMenuCategories();
        $availableBrands = $this->frontendProductService->getAvailableBrands($selectedSubCategoryId, $searchTerm, $selectedOfferId, $selectedPromoTileId);
        $products = $this->frontendProductService->getProductsByBrands(
            $selectedSubCategoryId,
            $searchTerm,
            $selectedOfferId,
            $selectedBrandIds,
            $selectedPromoTileId
        );

        return view('frontend.products', compact(
            'menuCategories',
            'products',
            'selectedSubCategory',
            'selectedOffer',
            'selectedPromoTile',
            'searchTerm',
            'availableBrands',
            'selectedBrandIds'
        ));
    }
}
