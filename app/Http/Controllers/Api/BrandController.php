<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::query()
            ->where('is_active', 1)
            ->orderBy('brand_name')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Brands fetched successfully',
            'data' => $brands,
        ]);
    }
}
