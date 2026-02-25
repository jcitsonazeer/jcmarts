<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class SubCategoryService
{
    public function getAll()
    {
        return SubCategory::with(['category', 'createdBy', 'updatedBy'])
            ->orderBy('id', 'desc')
            ->get();
    }
	
	// For Mobile API
	public function getActiveForApi()
{
    return SubCategory::with('category') // optional if app needs category
        ->orderBy('id', 'desc')
        ->get();
}

    public function getCategoriesForDropdown()
    {
        return Category::orderBy('category_name')->get();
    }

    public function findForShow($id)
    {
        return SubCategory::with(['category', 'createdBy', 'updatedBy'])->findOrFail($id);
    }

    public function findForEdit($id)
    {
        return SubCategory::with(['category', 'createdBy', 'updatedBy'])->findOrFail($id);
    }

    public function create($data, $adminId)
    {
        $imageName = null;
        if (!empty($data['sub_category_image'])) {
            $imageName = $this->storeSubCategoryImage($data['sub_category_image']);
        }

        return SubCategory::create([
            'category_id' => $data['category_id'],
            'sub_category_name' => $data['sub_category_name'],
            'sub_category_image' => $imageName,
            'created_by_id' => $adminId,
            'created_date' => Carbon::now(),
        ]);
    }

    public function update($id, $data, $adminId)
    {
        $subCategory = SubCategory::findOrFail($id);

        $imageName = $subCategory->sub_category_image;
        if (!empty($data['sub_category_image'])) {
            $imageName = $this->storeSubCategoryImage($data['sub_category_image']);

            if (!empty($subCategory->sub_category_image)) {
                Storage::disk('public')->delete('sub_category/' . $subCategory->sub_category_image);
            }
        }

        $subCategory->update([
            'category_id' => $data['category_id'],
            'sub_category_name' => $data['sub_category_name'],
            'sub_category_image' => $imageName,
            'updated_by_id' => $adminId,
            'updated_date' => Carbon::now(),
        ]);

        return $subCategory;
    }

    public function delete($id)
    {
        $subCategory = SubCategory::findOrFail($id);

        if (!empty($subCategory->sub_category_image)) {
            Storage::disk('public')->delete('sub_category/' . $subCategory->sub_category_image);
        }

        $subCategory->delete();
    }

    public function hasProducts($id)
    {
        return Product::where('sub_category_id', $id)->exists();
    }

    private function storeSubCategoryImage($image)
    {
        $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
        $sanitizedName = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $originalName);
        $sanitizedName = trim((string) $sanitizedName, '_');
        $sanitizedName = $sanitizedName !== '' ? $sanitizedName : 'sub_category';

        $fileName = $sanitizedName . '_' . time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
        $image->storeAs('sub_category', $fileName, 'public');

        return $fileName;
    }
}
