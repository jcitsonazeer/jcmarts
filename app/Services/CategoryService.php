<?php

namespace App\Services;

use App\Models\Category;
use App\Models\SubCategory;
use Carbon\Carbon;

class CategoryService
{
    public function getAll()
    {
        return Category::with(['createdBy', 'updatedBy'])
            ->orderBy('id', 'desc')
            ->get();
    }

    public function findForEdit($id)
    {
        return Category::with(['createdBy', 'updatedBy'])->findOrFail($id);
    }

    public function create($data, $adminId)
    {
        return Category::create([
            'category_name' => $data['category_name'],
            'created_by_id' => $adminId,
            'created_date' => Carbon::now(),
        ]);
    }

    public function update($id, $data, $adminId)
    {
        $category = Category::findOrFail($id);

        $category->update([
            'category_name' => $data['category_name'],
            'updated_by_id' => $adminId,
            'updated_date' => Carbon::now(),
        ]);

        return $category;
    }

    public function hasSubCategories($id)
    {
        return SubCategory::where('category_id', $id)->exists();
    }

    public function delete($id)
    {
        $category = Category::findOrFail($id);

        $category->delete();
    }
}
