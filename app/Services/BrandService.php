<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Product;
use Carbon\Carbon;

class BrandService
{
    public function getAll(?string $searchTerm = null)
    {
        return Brand::with(['createdBy', 'updatedBy'])
            ->when(!empty(trim((string) $searchTerm)), function ($query) use ($searchTerm) {
                $query->where('brand_name', 'like', '%' . trim((string) $searchTerm) . '%');
            })
            ->orderBy('id', 'desc')
            ->get();
    }

    public function getActiveForDropdown()
    {
        return Brand::where('is_active', 1)
            ->orderBy('brand_name')
            ->get();
    }

    public function findForShow($id)
    {
        return Brand::with(['createdBy', 'updatedBy'])->findOrFail($id);
    }

    public function findForEdit($id)
    {
        return Brand::with(['createdBy', 'updatedBy'])->findOrFail($id);
    }

    public function create($data, $adminId)
    {
        return Brand::create([
            'brand_name' => $data['brand_name'],
            'is_active' => $data['is_active'],
            'created_by_id' => $adminId,
            'created_date' => Carbon::now(),
        ]);
    }

    public function update($id, $data, $adminId)
    {
        $brand = Brand::findOrFail($id);

        $brand->update([
            'brand_name' => $data['brand_name'],
            'is_active' => $data['is_active'],
            'updated_by_id' => $adminId,
            'updated_date' => Carbon::now(),
        ]);

        return $brand;
    }

    public function hasProducts($id)
    {
        return Product::where('brand_id', $id)->exists();
    }

    public function delete($id)
    {
        $brand = Brand::findOrFail($id);
        $brand->delete();
    }
}
