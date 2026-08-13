<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SubCategoryService
{
    private const IMAGE_WIDTH = 180;
    private const IMAGE_HEIGHT = 135;

    public function getAll(?string $searchTerm = null)
    {
        $query = SubCategory::query()
            ->select([
                'id',
                'category_id',
                'sub_category_name',
                'sub_category_image',
                'created_by_id',
                'created_date',
                'updated_by_id',
                'updated_date',
            ])
            ->with(['category', 'createdBy', 'updatedBy']);

        $term = trim((string) $searchTerm);

        if ($term !== '') {
            $query->where(function ($innerQuery) use ($term) {
                $innerQuery->where('sub_category_name', 'like', '%' . $term . '%');

                $innerQuery->orWhereHas('category', function ($categoryQuery) use ($term) {
                    $categoryQuery->where('category_name', 'like', '%' . $term . '%');
                });
            });
        }

        return $query->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();
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

    private function storeSubCategoryImage(UploadedFile $image): string
    {
        $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
        $sanitizedName = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $originalName);
        $sanitizedName = trim((string) $sanitizedName, '_');

        if ($sanitizedName === '') {
            $sanitizedName = 'sub_category';
        }

        $imageInformation = getimagesize($image->getRealPath());
        if ($imageInformation === false) {
            throw new RuntimeException('Unable to read image size.');
        }

        $extension = null;
        if ($imageInformation[2] === IMAGETYPE_PNG) {
            $extension = 'png';
        }

        if ($imageInformation[2] === IMAGETYPE_JPEG) {
            $extension = 'jpg';
        }

        if ($extension === null) {
            throw new RuntimeException('Only JPG, JPEG, and PNG images are allowed.');
        }

        $fileName = $sanitizedName . '_' . time() . '_' . uniqid() . '.' . $extension;
        $resizedImage = $this->resizeImage($image, self::IMAGE_WIDTH, self::IMAGE_HEIGHT, $extension);
        Storage::disk('public')->put('sub_category/' . $fileName, $resizedImage);

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

        $sourceImage = null;

        if ($imageType === IMAGETYPE_JPEG && \function_exists('imagecreatefromjpeg')) {
            $sourceImage = \imagecreatefromjpeg($sourcePath);
        }

        if ($imageType === IMAGETYPE_PNG && \function_exists('imagecreatefrompng')) {
            $sourceImage = \imagecreatefrompng($sourcePath);
        }

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

        ob_start();

        if ($extension === 'png') {
            \imagepng($destinationImage);
        } else {
            \imagejpeg($destinationImage, null, 85);
        }

        $imageContents = (string) ob_get_clean();

        \imagedestroy($sourceImage);
        \imagedestroy($destinationImage);

        return $imageContents;
    }
}
