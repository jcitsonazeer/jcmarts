<?php

namespace App\Services;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ProductImageService
{
    private const IMAGE_WIDTH = 500;
    private const IMAGE_HEIGHT = 500;

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

    private function storeSingleImage(UploadedFile $image, string $field): string
    {
        $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
        $sanitizedName = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $originalName);
        $sanitizedName = trim((string) $sanitizedName, '_');
        $sanitizedName = $sanitizedName !== '' ? $sanitizedName : $field;

        $extension = strtolower((string) $image->getClientOriginalExtension());
        $extension = in_array($extension, ['jpg', 'jpeg', 'png'], true) ? $extension : 'jpg';

        $fileName = $sanitizedName . '_' . time() . '_' . uniqid() . '.' . $extension;
        $resizedImage = $this->resizeImage($image, self::IMAGE_WIDTH, self::IMAGE_HEIGHT, $extension);
        Storage::disk('public')->put('product/single/' . $fileName, $resizedImage);

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
}
