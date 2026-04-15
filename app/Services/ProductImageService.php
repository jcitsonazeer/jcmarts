<?php

namespace App\Services;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImageService
{
    private array $imageFields = [
        'single_image_1',
        'single_image_2',
        'single_image_3',
        'single_image_4',
    ];

    public function getAllProducts(?string $searchTerm = null)
    {
        return Product::query()
            ->select([
                'id',
                'sub_category_id',
                'product_name',
                'single_image_1',
                'single_image_2',
                'single_image_3',
                'single_image_4',
            ])
            ->with(['subCategory'])
            ->when(!empty(trim((string) $searchTerm)), function ($query) use ($searchTerm) {
                $term = trim((string) $searchTerm);

                $query->where(function ($innerQuery) use ($term) {
                    $innerQuery->where('product_name', 'like', '%' . $term . '%')
                        ->orWhereHas('subCategory', function ($subCategoryQuery) use ($term) {
                            $subCategoryQuery->where('sub_category_name', 'like', '%' . $term . '%');
                        });
                });
            })
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();
    }

    public function getProductsForDropdown()
    {
        return Product::orderBy('product_name')->get(['id', 'product_name']);
    }

    public function findProductIdByName(string $productName): ?int
    {
        $productName = trim($productName);

        if ($productName === '') {
            return null;
        }

        $product = Product::query()
            ->whereRaw('LOWER(product_name) = ?', [Str::lower($productName)])
            ->first(['id']);

        return $product ? (int) $product->id : null;
    }

    public function findProduct($id)
    {
        return Product::with(['subCategory'])->findOrFail($id);
    }

    public function storeForProduct(array $data, int $adminId)
    {
        $product = Product::findOrFail($data['product_id']);

        $updateData = [
            'updated_by_id' => $adminId,
            'updated_date' => Carbon::now(),
        ];

        foreach ($this->imageFields as $field) {
            if (!empty($data[$field])) {
                if (!empty($product->{$field})) {
                    Storage::disk('public')->delete('product/single/' . $product->{$field});
                }

                $updateData[$field] = $this->storeSingleImage($data[$field], $field);
            }
        }

        $product->update($updateData);

        return $product;
    }

    public function updateForProduct(int $productId, array $data, int $adminId)
    {
        $product = Product::findOrFail($productId);

        $updateData = [
            'updated_by_id' => $adminId,
            'updated_date' => Carbon::now(),
        ];

        foreach ($this->imageFields as $field) {
            if (!empty($data[$field])) {
                if (!empty($product->{$field})) {
                    Storage::disk('public')->delete('product/single/' . $product->{$field});
                }

                $updateData[$field] = $this->storeSingleImage($data[$field], $field);
            }
        }

        $product->update($updateData);

        return $product;
    }

    public function clearImages(int $productId, int $adminId)
    {
        $product = Product::findOrFail($productId);

        foreach ($this->imageFields as $field) {
            if (!empty($product->{$field})) {
                Storage::disk('public')->delete('product/single/' . $product->{$field});
            }
        }

        $product->update([
            'single_image_1' => null,
            'single_image_2' => null,
            'single_image_3' => null,
            'single_image_4' => null,
            'updated_by_id' => $adminId,
            'updated_date' => Carbon::now(),
        ]);

        return $product;
    }

    public function hasAnySingleImage(Product $product): bool
    {
        foreach ($this->imageFields as $field) {
            if (!empty($product->{$field})) {
                return true;
            }
        }

        return false;
    }

    private function storeSingleImage($image, string $field): string
    {
        $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
        $sanitizedName = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $originalName);
        $sanitizedName = trim((string) $sanitizedName, '_');
        $sanitizedName = $sanitizedName !== '' ? $sanitizedName : $field;

        $fileName = $sanitizedName . '_' . time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
        $image->storeAs('product/single', $fileName, 'public');

        return $fileName;
    }
}
