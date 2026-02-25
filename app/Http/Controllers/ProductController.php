<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index()
    {
        $products = $this->productService->getAll();

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        $subCategories = $this->productService->getSubCategoriesForDropdown();

        return view('admin.products.create', compact('subCategories'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'product_name' => $this->normalizeProductName($request->input('product_name')),
        ]);

        $validatedData = $request->validate([
            'sub_category_id' => 'required|integer|exists:sub_category,id',
            'product_name' => 'required|string|max:150|unique:products,product_name',
            'product_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'description' => 'nullable|string|max:1000',
            'warranty_info' => 'nullable|string|max:500',
            'is_active' => 'required|boolean',
        ]);

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        $this->productService->create($validatedData, $adminId);

        return redirect()->route('admin.products.index')
            ->with('success', 'Product created successfully');
    }

    public function show($id)
    {
        $product = $this->productService->findForShow($id);

        return view('admin.products.show', compact('product'));
    }

    public function edit($id)
    {
        $product = $this->productService->findForEdit($id);
        $subCategories = $this->productService->getSubCategoriesForDropdown();

        return view('admin.products.edit', compact('product', 'subCategories'));
    }

    public function update(Request $request, $id)
    {
        $request->merge([
            'product_name' => $this->normalizeProductName($request->input('product_name')),
        ]);

        $validatedData = $request->validate([
            'sub_category_id' => 'required|integer|exists:sub_category,id',
            'product_name' => 'required|string|max:150|unique:products,product_name,' . $id,
            'product_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'description' => 'nullable|string|max:1000',
            'warranty_info' => 'nullable|string|max:500',
            'is_active' => 'required|boolean',
        ]);

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        $this->productService->update($id, $validatedData, $adminId);

        return redirect()->route('admin.products.edit', $id)
            ->with('success', 'Product updated successfully');
    }

    public function destroy($id)
    {
        if ($this->productService->hasRateMasters($id)) {
            return redirect()->route('admin.products.index')
                ->with('error', 'This product is already added in rate master and cannot be deleted.');
        }

        $this->productService->delete($id);

        return redirect()->route('admin.products.index')
            ->with('success', 'Product deleted successfully');
    }

    private function normalizeProductName($name)
    {
        $name = trim((string) $name);
        $name = preg_replace('/\s+/', ' ', $name);

        return Str::title((string) $name);
    }
}
