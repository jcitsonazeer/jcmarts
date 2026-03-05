<?php

namespace App\Services;

use App\Models\IndexBanner;
use App\Models\OfferDetail;
use App\Models\OfferProduct;
use Carbon\Carbon;

class OfferDetailService
{
    public function getAll()
    {
        return OfferDetail::with(['createdBy', 'updatedBy'])
            ->orderByDesc('id')
            ->get();
    }

    public function getActiveForDropdown()
    {
        return OfferDetail::query()
            ->where('is_active', 1)
            ->orderBy('offer_name')
            ->get();
    }

    public function findForShow($id)
    {
        return OfferDetail::with(['createdBy', 'updatedBy'])->findOrFail($id);
    }

    public function findForEdit($id)
    {
        return OfferDetail::findOrFail($id);
    }

    public function create(array $data, int $adminId)
    {
        return OfferDetail::create([
            'offer_name' => $data['offer_name'],
            'is_active' => $data['is_active'],
            'created_by_id' => $adminId,
            'created_date' => Carbon::now(),
        ]);
    }

    public function update(int $id, array $data, int $adminId)
    {
        $offer = OfferDetail::findOrFail($id);

        $offer->update([
            'offer_name' => $data['offer_name'],
            'is_active' => $data['is_active'],
            'updated_by_id' => $adminId,
            'updated_date' => Carbon::now(),
        ]);

        return $offer;
    }

    public function delete(int $id)
    {
        $offer = OfferDetail::findOrFail($id);
        $offer->delete();
    }

    public function hasOfferProducts(int $id): bool
    {
        return OfferProduct::where('offer_id', $id)->exists();
    }

    public function hasIndexBanners(int $id): bool
    {
        return IndexBanner::where('offer_details_id', $id)->exists();
    }
}
