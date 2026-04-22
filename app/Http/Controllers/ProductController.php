<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));
        $products = $this->productService->getAll($search);

        return view('admin.products.index', compact('products', 'search'));
    }

    public function create()
    {
        $subCategories = $this->productService->getSubCategoriesForDropdown();
        $brands = $this->productService->getBrandsForDropdown();

        return view('admin.products.create', compact('subCategories', 'brands'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'sub_category_name' => trim((string) $request->input('sub_category_name')),
            'brand_name' => trim((string) $request->input('brand_name')),
            'product_name' => $this->normalizeProductName($request->input('product_name')),
        ]);

        $validatedData = $request->validate([
            'sub_category_name' => 'required|string|max:100',
            'brand_name' => 'nullable|string|max:120',
            'product_name' => 'required|string|max:150|unique:products,product_name',
            'product_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'description' => 'nullable|string|max:1000',
            'warranty_info' => 'nullable|string|max:500',
            'is_active' => 'required|boolean',
        ]);

        $validatedData['sub_category_id'] = $this->productService->findSubCategoryIdByName($validatedData['sub_category_name']);

        if (!$validatedData['sub_category_id']) {
            return back()
                ->withErrors(['sub_category_name' => 'Please select a valid sub category from the suggestion list.'])
                ->withInput();
        }

        $validatedData['brand_id'] = $this->productService->findBrandIdByName($validatedData['brand_name'] ?? null);

        if (!empty($validatedData['brand_name']) && !$validatedData['brand_id']) {
            return back()
                ->withErrors(['brand_name' => 'Please select a valid brand from the suggestion list.'])
                ->withInput();
        }

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        try {
            $this->productService->create($validatedData, $adminId);
        } catch (RuntimeException $exception) {
            return back()
                ->withErrors(['product_image' => $exception->getMessage()])
                ->withInput();
        }

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
        $brands = $this->productService->getBrandsForDropdown();

        return view('admin.products.edit', compact('product', 'subCategories', 'brands'));
    }

    public function update(Request $request, $id)
    {
        $request->merge([
            'sub_category_name' => trim((string) $request->input('sub_category_name')),
            'brand_name' => trim((string) $request->input('brand_name')),
            'product_name' => $this->normalizeProductName($request->input('product_name')),
        ]);

        $validatedData = $request->validate([
            'sub_category_id' => 'nullable|integer|exists:sub_category,id',
            'brand_id' => 'nullable|integer|exists:brands,id',
            'sub_category_name' => 'nullable|string|max:100',
            'brand_name' => 'nullable|string|max:120',
            'product_name' => 'required|string|max:150|unique:products,product_name,' . $id,
            'product_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'description' => 'nullable|string|max:1000',
            'warranty_info' => 'nullable|string|max:500',
            'is_active' => 'required|boolean',
        ]);

        if (empty($validatedData['sub_category_id']) && !empty($validatedData['sub_category_name'])) {
            $validatedData['sub_category_id'] = $this->productService->findSubCategoryIdByName($validatedData['sub_category_name']);
        }

        if (array_key_exists('brand_name', $validatedData) && $validatedData['brand_name'] !== null && $validatedData['brand_name'] !== '') {
            $validatedData['brand_id'] = $this->productService->findBrandIdByName($validatedData['brand_name']);
        }

        $validator = Validator::make($validatedData, [
            'sub_category_id' => 'required|integer|exists:sub_category,id',
            'brand_id' => 'nullable|integer|exists:brands,id',
        ], [
            'sub_category_id.required' => 'Please select a valid sub category from the suggestion list.',
            'brand_id.exists' => 'Please select a valid brand from the suggestion list.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        try {
            $this->productService->update($id, $validatedData, $adminId);
        } catch (RuntimeException $exception) {
            return back()
                ->withErrors(['product_image' => $exception->getMessage()])
                ->withInput();
        }

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
