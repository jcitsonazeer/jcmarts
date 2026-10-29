<?php

namespace App\Services;

use App\Models\IndexBanner;
use App\Models\OfferDetail;
use App\Models\SubCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IndexBannerService
{
    public function getAll()
    {
        return IndexBanner::query()
            ->select([
                'id',
                'banner_image',
                'sub_category_id',
                'offer_details_id',
                'created_by_id',
                'created_date',
                'updated_by_id',
                'updated_date',
            ])
            ->with(['createdBy', 'updatedBy', 'subCategory', 'offerDetail'])
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();
    }

    public function findForShow($id)
    {
        return IndexBanner::with(['createdBy', 'updatedBy', 'subCategory', 'offerDetail'])->findOrFail($id);
    }

    public function findForEdit($id)
    {
        return IndexBanner::with(['createdBy', 'updatedBy', 'subCategory', 'offerDetail'])->findOrFail($id);
    }

    public function getSubCategoriesForDropdown()
    {
        return SubCategory::query()
            ->orderBy('sub_category_name')
            ->get();
    }

    public function findSubCategoryIdByName(?string $subCategoryName): ?int
    {
        $subCategoryName = trim((string) $subCategoryName);

        if ($subCategoryName === '') {
            return null;
        }

        $subCategory = SubCategory::query()
            ->whereRaw('LOWER(sub_category_name) = ?', [Str::lower($subCategoryName)])
            ->first(['id']);

        return $subCategory ? (int) $subCategory->id : null;
    }

    public function getOffersForDropdown()
    {
        return OfferDetail::query()
            ->where('is_active', 1)
            ->orderBy('offer_name')
            ->get();
    }

    public function create($data, $adminId)
    {
        $imageName = null;
        if (!empty($data['banner_image'])) {
            $imageName = $this->storeBannerImage($data['banner_image']);
        }

        return IndexBanner::create([
            'banner_image' => $imageName,
            'sub_category_id' => $data['sub_category_id'] ?? null,
            'offer_details_id' => $data['offer_details_id'] ?? null,
            'created_by_id' => $adminId,
            'created_date' => Carbon::now(),
        ]);
    }

    public function update($id, $data, $adminId)
    {
        $banner = IndexBanner::findOrFail($id);

        $imageName = $banner->banner_image;
        if (!empty($data['banner_image'])) {
            $imageName = $this->storeBannerImage($data['banner_image']);

            if (!empty($banner->banner_image)) {
                Storage::disk('public')->delete('index_banner/' . $banner->banner_image);
            }
        }

        $banner->update([
            'banner_image' => $imageName,
            'sub_category_id' => $data['sub_category_id'] ?? null,
            'offer_details_id' => $data['offer_details_id'] ?? null,
            'updated_by_id' => $adminId,
            'updated_date' => Carbon::now(),
        ]);

        return $banner;
    }

    public function delete($id)
    {
        $banner = IndexBanner::findOrFail($id);

        if (!empty($banner->banner_image)) {
            Storage::disk('public')->delete('index_banner/' . $banner->banner_image);
        }

        $banner->delete();
    }

    private function storeBannerImage($image)
    {
        $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
        $sanitizedName = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $originalName);
        $sanitizedName = trim((string) $sanitizedName, '_');
        $sanitizedName = $sanitizedName !== '' ? $sanitizedName : 'banner';

        // Derive the extension from the detected MIME type, never from the client.
        $extension = strtolower((string) $image->extension());
        $extension = in_array($extension, ['jpg', 'jpeg', 'png'], true) ? $extension : 'jpg';

        $fileName = $sanitizedName . '_' . time() . '_' . uniqid() . '.' . $extension;

        // Re-encode with GD to strip any embedded metadata/EXIF.
        $sourcePath = $image->getRealPath();
        if (!$sourcePath) {
            throw new \RuntimeException('Unable to read the uploaded image.');
        }

        $imageInformation = \getimagesize($sourcePath);
        if ($imageInformation === false) {
            throw new \RuntimeException('Unable to read image size.');
        }

        [$sourceWidth, $sourceHeight, $imageType] = $imageInformation;

        $sourceImage = null;
        if ($imageType === IMAGETYPE_JPEG && \function_exists('imagecreatefromjpeg')) {
            $sourceImage = \imagecreatefromjpeg($sourcePath);
        } elseif ($imageType === IMAGETYPE_PNG && \function_exists('imagecreatefrompng')) {
            $sourceImage = \imagecreatefrompng($sourcePath);
        }

        if (!$sourceImage) {
            throw new \RuntimeException('Only JPG, JPEG, and PNG images are allowed.');
        }

        // Keep the extension in sync with the actual decoded image type.
        if ($imageType === IMAGETYPE_PNG) {
            $extension = 'png';
        } else {
            $extension = 'jpg';
        }

        // Rebuild filename with the verified extension.
        $fileName = $sanitizedName . '_' . time() . '_' . uniqid() . '.' . $extension;

        \ob_start();
        if ($imageType === IMAGETYPE_PNG) {
            \imagepng($sourceImage);
        } else {
            \imagejpeg($sourceImage, null, 90);
        }
        $imageContents = (string) \ob_get_clean();

        \imagedestroy($sourceImage);

        Storage::disk('public')->put('index_banner/' . $fileName, $imageContents);

        return $fileName;
    }
}
