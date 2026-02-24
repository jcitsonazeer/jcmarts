<?php

namespace App\Http\Controllers;

use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function index()
    {
        $categories = $this->categoryService->getAll();
        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $request->merge([
            'category_name' => Str::title(trim((string) $request->category_name)),
        ]);

        $validatedData = $request->validate([
            'category_name' => 'required|string|max:100|unique:category,category_name',
        ]);

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        $this->categoryService->create($validatedData, $adminId);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully');
    }

    public function edit($id)
    {
        $category = $this->categoryService->findForEdit($id);
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, $id)
    {
        $request->merge([
            'category_name' => Str::title(trim((string) $request->category_name)),
        ]);

        $validatedData = $request->validate([
            'category_name' => 'required|string|max:100|unique:category,category_name,' . $id,
        ]);

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        $this->categoryService->update($id, $validatedData, $adminId);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully');
    }

    public function destroy($id)
    {
        if ($this->categoryService->hasSubCategories($id)) {
            return redirect()->route('admin.categories.index')
                ->with('error', 'This category added for a sub category');
        }

        $this->categoryService->delete($id);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category deleted successfully');
    }
}
