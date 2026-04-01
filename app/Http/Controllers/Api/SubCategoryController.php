<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SubCategoryService;
use Illuminate\Http\Request;

class SubCategoryController extends Controller
{
    protected $subCategoryService;

    public function __construct(SubCategoryService $subCategoryService)
    {
        $this->subCategoryService = $subCategoryService;
    }

    public function index()
    {
        $subCategories = $this->subCategoryService->getActiveForApi();

        $subCategories->transform(function ($subCategory) {
            $subCategory->sub_category_image = $subCategory->sub_category_image
                ? asset('storage/sub_category/' . $subCategory->sub_category_image)
                : null;

            return $subCategory;
        });

        return response()->json([
            'status' => true,
            'message' => 'Active sub categories fetched successfully',
            'data' => $subCategories,
        ]);
    }
}
