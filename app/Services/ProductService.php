<?php

namespace App\Services;

use App\Models\Product;
use App\Models\RateMaster;
use App\Models\SubCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService
{
    public function getAll()
    {
        return Product::with(['subCategory', 'createdBy', 'updatedBy'])
            ->orderBy('id', 'desc')
            ->get();
    }
	
// For Mobile API
public function getActiveProductsForApi($subCategoryId = null)
{
    $query = Product::query()
        ->where('is_active', 1)
        ->whereHas('rates', function ($query) {
            $query->where('is_active', 1);
        })
        ->with([
            'subCategory',
            'rates' => function ($query) {
                $query->where('is_active', 1)
                      ->with('uom')
                      ->orderBy('id');
            },
        ]);

    if ($subCategoryId) {
        $query->where('sub_category_id', $subCategoryId);
    }

    return $query->orderByDesc('id')
                 ->paginate(10);
}

    public function getSubCategoriesForDropdown()
    {
        return SubCategory::orderBy('sub_category_name')->get();
    }

    public function findForShow($id)
    {
        return Product::with(['subCategory', 'createdBy', 'updatedBy'])->findOrFail($id);
    }

    public function findForEdit($id)
    {
        return Product::with(['subCategory', 'createdBy', 'updatedBy'])->findOrFail($id);
    }

    public function create($data, $adminId)
    {
        $productName = $this->normalizeProductName($data['product_name'] ?? '');

        $imageName = null;
        if (!empty($data['product_image'])) {
            $imageName = $this->storeProductImage($data['product_image']);
        }

        return Product::create([
            'sub_category_id' => $data['sub_category_id'],
            'product_name' => $productName,
            'product_image' => $imageName,
            'description' => $data['description'] ?? null,
            'warranty_info' => $data['warranty_info'] ?? null,
            'is_active' => $data['is_active'],
            'created_by_id' => $adminId,
            'created_date' => Carbon::now(),
        ]);
    }

    public function update($id, $data, $adminId)
    {
        $product = Product::findOrFail($id);
        $productName = $this->normalizeProductName($data['product_name'] ?? '');

        $imageName = $product->product_image;
        if (!empty($data['product_image'])) {
            $imageName = $this->storeProductImage($data['product_image']);

            if (!empty($product->product_image)) {
                Storage::disk('public')->delete('product/' . $product->product_image);
            }
        }

        $product->update([
            'sub_category_id' => $data['sub_category_id'],
            'product_name' => $productName,
            'product_image' => $imageName,
            'description' => $data['description'] ?? null,
            'warranty_info' => $data['warranty_info'] ?? null,
            'is_active' => $data['is_active'],
            'updated_by_id' => $adminId,
            'updated_date' => Carbon::now(),
        ]);

        return $product;
    }

    public function delete($id)
    {
        $product = Product::findOrFail($id);

        if (!empty($product->product_image)) {
            Storage::disk('public')->delete('product/' . $product->product_image);
        }

        $product->delete();
    }

    public function hasRateMasters($id)
    {
        return RateMaster::where('product_id', $id)->exists();
    }

    private function storeProductImage($image)
    {
        $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
        $sanitizedName = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $originalName);
        $sanitizedName = trim((string) $sanitizedName, '_');
        $sanitizedName = $sanitizedName !== '' ? $sanitizedName : 'product';

        $fileName = $sanitizedName . '_' . time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
        $image->storeAs('product', $fileName, 'public');

        return $fileName;
    }

    private function normalizeProductName($name)
    {
        $name = trim((string) $name);
        $name = preg_replace('/\s+/', ' ', $name);

        return Str::title((string) $name);
    }
}
