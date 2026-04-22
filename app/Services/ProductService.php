<?php

namespace App\Services;

use App\Models\Product;
use App\Models\RateMaster;
use App\Models\Brand;
use App\Models\SubCategory;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ProductService
{
    private const IMAGE_WIDTH = 233;
    private const IMAGE_HEIGHT = 215;

    public function getAll(?string $searchTerm = null)
    {
        return Product::query()
            ->select([
                'id',
                'sub_category_id',
                'brand_id',
                'product_name',
                'product_image',
                'is_active',
                'created_by_id',
                'updated_by_id',
            ])
            ->with(['subCategory', 'brand', 'createdBy', 'updatedBy'])
            ->when(!empty(trim((string) $searchTerm)), function ($query) use ($searchTerm) {
                $term = trim((string) $searchTerm);

                $query->where(function ($innerQuery) use ($term) {
                    $innerQuery->where('product_name', 'like', '%' . $term . '%')
                        ->orWhereHas('subCategory', function ($subCategoryQuery) use ($term) {
                            $subCategoryQuery->where('sub_category_name', 'like', '%' . $term . '%');
                        })
                        ->orWhereHas('brand', function ($brandQuery) use ($term) {
                            $brandQuery->where('brand_name', 'like', '%' . $term . '%');
                        });
                });
            })
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();
    }
	
    // For Mobile API
    public function getActiveProductsForApi($subCategoryId = null, $search = null, $perPage = 10)
    {
        $query = Product::query()
            ->where('is_active', 1)
            ->whereHas('rates', function ($query) {
                $query->where('is_active', 1);
            })
            ->when(!empty($subCategoryId), function ($query) use ($subCategoryId) {
                $query->where('sub_category_id', $subCategoryId);
            })
            ->when(!empty($search), function ($query) use ($search) {
                $query->where('product_name', 'like', '%' . $search . '%');
            })
            ->with([
                'subCategory',
                'rates' => function ($query) {
                    $query->where('is_active', 1)
                        ->with('uom')
                        ->orderByDesc('selected_display')
                        ->orderBy('id');
                },
            ]);

        return $query->orderByDesc('id')
            ->paginate($perPage);
    }

    public function getSubCategoriesForDropdown()
    {
        return SubCategory::orderBy('sub_category_name')->get();
    }

    public function getBrandsForDropdown()
    {
        return Brand::where('is_active', 1)->orderBy('brand_name')->get();
    }

    public function findSubCategoryIdByName(string $subCategoryName): ?int
    {
        $subCategoryName = trim($subCategoryName);

        if ($subCategoryName === '') {
            return null;
        }

        $subCategory = SubCategory::query()
            ->whereRaw('LOWER(sub_category_name) = ?', [Str::lower($subCategoryName)])
            ->first();

        return $subCategory ? (int) $subCategory->id : null;
    }

    public function findBrandIdByName(?string $brandName): ?int
    {
        $brandName = trim((string) $brandName);

        if ($brandName === '') {
            return null;
        }

        $brand = Brand::query()
            ->where('is_active', 1)
            ->whereRaw('LOWER(brand_name) = ?', [Str::lower($brandName)])
            ->first();

        return $brand ? (int) $brand->id : null;
    }

    public function findForShow($id)
    {
        return Product::with(['subCategory', 'brand', 'createdBy', 'updatedBy'])->findOrFail($id);
    }

    public function findForEdit($id)
    {
        return Product::with(['subCategory', 'brand', 'createdBy', 'updatedBy'])->findOrFail($id);
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
            'brand_id' => $data['brand_id'] ?? null,
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
            'brand_id' => $data['brand_id'] ?? null,
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

    private function storeProductImage(UploadedFile $image): string
    {
        $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
        $sanitizedName = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $originalName);
        $sanitizedName = trim((string) $sanitizedName, '_');
        $sanitizedName = $sanitizedName !== '' ? $sanitizedName : 'product';

        $extension = strtolower((string) $image->getClientOriginalExtension());
        $extension = in_array($extension, ['jpg', 'jpeg', 'png'], true) ? $extension : 'jpg';

        $fileName = $sanitizedName . '_' . time() . '_' . uniqid() . '.' . $extension;
        $resizedImage = $this->resizeImage($image, self::IMAGE_WIDTH, self::IMAGE_HEIGHT, $extension);
        Storage::disk('public')->put('product/' . $fileName, $resizedImage);

        return $fileName;
    }

    private function resizeImage(UploadedFile $image, int $targetWidth, int $targetHeight, string $extension): string
    {
        if (
            !\extension_loaded('gd')
            || !\function_exists('imagecreatetruecolor')
            || !\function_exists('imagecopyresampled')
        ) {
            throw new RuntimeException('Server image resize support is not enabled. Please enable PHP GD extension.');
        }

        $sourcePath = $image->getRealPath();
        if (!$sourcePath) {
            throw new RuntimeException('Unable to read the uploaded image.');
        }

        $imageInformation = \getimagesize($sourcePath);
        if ($imageInformation === false) {
            throw new RuntimeException('Unable to read image size.');
        }

        [$sourceWidth, $sourceHeight, $imageType] = $imageInformation;

        $sourceImage = match ($imageType) {
            IMAGETYPE_JPEG => \function_exists('imagecreatefromjpeg') ? \imagecreatefromjpeg($sourcePath) : null,
            IMAGETYPE_PNG => \function_exists('imagecreatefrompng') ? \imagecreatefrompng($sourcePath) : null,
            default => null,
        };

        if (!$sourceImage) {
            throw new RuntimeException('Only JPG, JPEG, and PNG images are allowed.');
        }

        $destinationImage = \imagecreatetruecolor($targetWidth, $targetHeight);

        if ($imageType === IMAGETYPE_PNG) {
            \imagealphablending($destinationImage, false);
            \imagesavealpha($destinationImage, true);
            $transparent = \imagecolorallocatealpha($destinationImage, 0, 0, 0, 127);
            \imagefill($destinationImage, 0, 0, $transparent);
        }

        $sourceRatio = $sourceWidth / $sourceHeight;
        $targetRatio = $targetWidth / $targetHeight;

        if ($sourceRatio > $targetRatio) {
            $cropHeight = $sourceHeight;
            $cropWidth = (int) round($sourceHeight * $targetRatio);
            $srcX = (int) round(($sourceWidth - $cropWidth) / 2);
            $srcY = 0;
        } else {
            $cropWidth = $sourceWidth;
            $cropHeight = (int) round($sourceWidth / $targetRatio);
            $srcX = 0;
            $srcY = (int) round(($sourceHeight - $cropHeight) / 2);
        }

        \imagecopyresampled(
            $destinationImage,
            $sourceImage,
            0,
            0,
            $srcX,
            $srcY,
            $targetWidth,
            $targetHeight,
            $cropWidth,
            $cropHeight
        );

        \ob_start();

        match ($extension) {
            'png' => \imagepng($destinationImage),
            default => \imagejpeg($destinationImage, null, 85),
        };

        $imageContents = (string) \ob_get_clean();

        \imagedestroy($sourceImage);
        \imagedestroy($destinationImage);

        return $imageContents;
    }

    private function normalizeProductName($name)
    {
        $name = trim((string) $name);
        $name = preg_replace('/\s+/', ' ', $name);

        return Str::title((string) $name);
    }
}
