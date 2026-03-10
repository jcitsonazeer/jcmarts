<?php

namespace App\Http\Controllers;

use App\Services\BrandService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    protected $brandService;

    public function __construct(BrandService $brandService)
    {
        $this->brandService = $brandService;
    }

    public function index()
    {
        $brands = $this->brandService->getAll();

        return view('admin.brands.index', compact('brands'));
    }

    public function create()
    {
        return view('admin.brands.create');
    }

    public function store(Request $request)
    {
        $request->merge([
            'brand_name' => Str::title(trim((string) $request->brand_name)),
        ]);

        $validatedData = $request->validate([
            'brand_name' => 'required|string|max:120|unique:brands,brand_name',
            'is_active' => 'required|boolean',
        ]);

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        $this->brandService->create($validatedData, $adminId);

        return redirect()->route('admin.brands.index')
            ->with('success', 'Brand created successfully');
    }

    public function show($id)
    {
        $brand = $this->brandService->findForShow($id);

        return view('admin.brands.show', compact('brand'));
    }

    public function edit($id)
    {
        $brand = $this->brandService->findForEdit($id);

        return view('admin.brands.edit', compact('brand'));
    }

    public function update(Request $request, $id)
    {
        $request->merge([
            'brand_name' => Str::title(trim((string) $request->brand_name)),
        ]);

        $validatedData = $request->validate([
            'brand_name' => 'required|string|max:120|unique:brands,brand_name,' . $id,
            'is_active' => 'required|boolean',
        ]);

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        $this->brandService->update($id, $validatedData, $adminId);

        return redirect()->route('admin.brands.edit', $id)
            ->with('success', 'Brand updated successfully');
    }

    public function destroy($id)
    {
        if ($this->brandService->hasProducts($id)) {
            return redirect()->route('admin.brands.index')
                ->with('error', 'This brand is associated with products and cannot be deleted.');
        }

        $this->brandService->delete($id);

        return redirect()->route('admin.brands.index')
            ->with('success', 'Brand deleted successfully');
    }
}
