<?php

namespace App\Http\Controllers;

use App\Services\ProductImageService;
use Illuminate\Http\Request;

class ProductImageController extends Controller
{
    protected $productImageService;

    public function __construct(ProductImageService $productImageService)
    {
        $this->productImageService = $productImageService;
    }

    public function index()
    {
        $products = $this->productImageService->getAllProducts();

        return view('admin.product_images.index', compact('products'));
    }

    public function create()
    {
        $products = $this->productImageService->getProductsForDropdown();

        return view('admin.product_images.create', compact('products'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'single_image_1' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048|required_without_all:single_image_2,single_image_3,single_image_4',
            'single_image_2' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048|required_without_all:single_image_1,single_image_3,single_image_4',
            'single_image_3' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048|required_without_all:single_image_1,single_image_2,single_image_4',
            'single_image_4' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048|required_without_all:single_image_1,single_image_2,single_image_3',
        ]);

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        $this->productImageService->storeForProduct($validatedData, (int) $adminId);

        return redirect()->route('admin.product-images.index')
            ->with('success', 'Product images saved successfully');
    }

    public function edit($productId)
    {
        $product = $this->productImageService->findProduct($productId);

        return view('admin.product_images.edit', compact('product'));
    }

    public function update(Request $request, $productId)
    {
        $validatedData = $request->validate([
            'single_image_1' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'single_image_2' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'single_image_3' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'single_image_4' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        $this->productImageService->updateForProduct((int) $productId, $validatedData, (int) $adminId);

        return redirect()->route('admin.product-images.edit', $productId)
            ->with('success', 'Product images updated successfully');
    }

    public function destroy($productId)
    {
        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        $this->productImageService->clearImages((int) $productId, (int) $adminId);

        return redirect()->route('admin.product-images.index')
            ->with('success', 'Product images deleted successfully');
    }
}
