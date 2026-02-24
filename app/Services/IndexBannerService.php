<?php

namespace App\Services;

use App\Models\IndexBanner;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class IndexBannerService
{
    public function getAll()
    {
        return IndexBanner::with(['createdBy', 'updatedBy'])
            ->orderBy('id', 'desc')
            ->get();
    }

    public function findForShow($id)
    {
        return IndexBanner::with(['createdBy', 'updatedBy'])->findOrFail($id);
    }

    public function findForEdit($id)
    {
        return IndexBanner::with(['createdBy', 'updatedBy'])->findOrFail($id);
    }

    public function create($data, $adminId)
    {
        $imageName = null;
        if (!empty($data['banner_image'])) {
            $imageName = $this->storeBannerImage($data['banner_image']);
        }

        return IndexBanner::create([
            'banner_image' => $imageName,
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

        $fileName = $sanitizedName . '_' . time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
        $image->storeAs('index_banner', $fileName, 'public');

        return $fileName;
    }
}
