<?php

namespace App\Http\Controllers;

use App\Services\SubCategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

class SubCategoryController extends Controller
{
    protected $subCategoryService;

    public function __construct(SubCategoryService $subCategoryService)
    {
        $this->subCategoryService = $subCategoryService;
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));
        $subCategories = $this->subCategoryService->getAll($search);

        return view('admin.sub_category.index', compact('subCategories', 'search'));
    }

    public function create()
    {
        $categories = $this->subCategoryService->getCategoriesForDropdown();

        return view('admin.sub_category.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'sub_category_name' => Str::title(trim((string) $request->sub_category_name)),
        ]);

        $validatedData = $request->validate([
            'category_id' => 'required|integer|exists:category,id',
            'sub_category_name' => 'required|string|max:100|unique:sub_category,sub_category_name',
            'sub_category_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        try {
            $this->subCategoryService->create($validatedData, $adminId);
        } catch (RuntimeException $exception) {
            return back()
                ->withErrors(['sub_category_image' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()->route('admin.sub-categories.index')
            ->with('success', 'Sub category created successfully');
    }

    public function show($id)
    {
        $subCategory = $this->subCategoryService->findForShow($id);

        return view('admin.sub_category.show', compact('subCategory'));
    }

    public function edit($id)
    {
        $subCategory = $this->subCategoryService->findForEdit($id);
        $categories = $this->subCategoryService->getCategoriesForDropdown();

        return view('admin.sub_category.edit', compact('subCategory', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $request->merge([
            'sub_category_name' => Str::title(trim((string) $request->sub_category_name)),
        ]);

        $validatedData = $request->validate([
            'category_id' => 'required|integer|exists:category,id',
            'sub_category_name' => 'required|string|max:100|unique:sub_category,sub_category_name,' . $id,
            'sub_category_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        try {
            $this->subCategoryService->update($id, $validatedData, $adminId);
        } catch (RuntimeException $exception) {
            return back()
                ->withErrors(['sub_category_image' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()->route('admin.sub-categories.edit', $id)
            ->with('success', 'Sub category updated successfully');
    }

    public function destroy($id)
    {
        if ($this->subCategoryService->hasProducts($id)) {
            return redirect()->route('admin.sub-categories.index')
                ->with('error', 'this sub category is added in products');
        }

        $this->subCategoryService->delete($id);

        return redirect()->route('admin.sub-categories.index')
            ->with('success', 'Sub category deleted successfully');
    }
}
