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

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));
        $products = $this->productImageService->getAllProducts($search);

        return view('admin.product_images.index', compact('products', 'search'));
    }

    public function create()
    {
        $products = $this->productImageService->getProductsForDropdown();
        $selectedProductName = '';

        if (request()->filled('product_id')) {
            $selectedProductName = optional(
                $products->firstWhere('id', (int) request('product_id'))
            )->product_name ?? '';
        }

        return view('admin.product_images.create', compact('products', 'selectedProductName'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'product_name' => trim((string) $request->input('product_name')),
        ]);

        $validatedData = $request->validate([
            'product_name' => 'required|string|max:150',
            'single_image_1' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048|required_without_all:single_image_2,single_image_3,single_image_4',
            'single_image_2' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048|required_without_all:single_image_1,single_image_3,single_image_4',
            'single_image_3' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048|required_without_all:single_image_1,single_image_2,single_image_4',
            'single_image_4' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048|required_without_all:single_image_1,single_image_2,single_image_3',
        ]);

        $validatedData['product_id'] = $this->productImageService->findProductIdByName($validatedData['product_name']);

        if (!$validatedData['product_id']) {
            return back()
                ->withErrors(['product_name' => 'Please select a valid product from the suggestion list.'])
                ->withInput();
        }

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
