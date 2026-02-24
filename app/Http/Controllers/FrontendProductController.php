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
        $selectedSubCategory = $this->frontendProductService->getSelectedSubCategory($subCategoryId);

        $menuCategories = $this->frontendProductService->getMenuCategories();
        $products = $this->frontendProductService->getProducts($selectedSubCategory?->id);

        return view('frontend.products', compact('menuCategories', 'products', 'selectedSubCategory'));
    }
}
