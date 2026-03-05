<?php

namespace App\Services;

use App\Models\OfferDetail;
use App\Models\OfferProduct;
use App\Models\Product;
use Carbon\Carbon;

class OfferProductService
{
    public function getAll()
    {
        return OfferProduct::with(['offer', 'product', 'createdBy', 'updatedBy'])
            ->orderByDesc('id')
            ->get();
    }

    public function getOffersForDropdown()
    {
        return OfferDetail::query()
            ->where('is_active', 1)
            ->orderBy('offer_name')
            ->get();
    }

    public function getProductsForDropdown()
    {
        return Product::query()
            ->where('is_active', 1)
            ->orderBy('product_name')
            ->get();
    }

    public function findForShow($id)
    {
        return OfferProduct::with(['offer', 'product', 'createdBy', 'updatedBy'])->findOrFail($id);
    }

    public function findForEdit($id)
    {
        return OfferProduct::findOrFail($id);
    }

    public function create(array $data, int $adminId)
    {
        return OfferProduct::create([
            'offer_id' => $data['offer_id'],
            'products_id' => $data['products_id'],
            'is_active' => $data['is_active'],
            'created_by_id' => $adminId,
            'created_date' => Carbon::now(),
        ]);
    }

    public function update(int $id, array $data, int $adminId)
    {
        $offerProduct = OfferProduct::findOrFail($id);

        $offerProduct->update([
            'offer_id' => $data['offer_id'],
            'products_id' => $data['products_id'],
            'is_active' => $data['is_active'],
            'updated_by_id' => $adminId,
            'updated_date' => Carbon::now(),
        ]);

        return $offerProduct;
    }

    public function delete(int $id)
    {
        $offerProduct = OfferProduct::findOrFail($id);
        $offerProduct->delete();
    }

    public function existsDuplicate(int $offerId, int $productId, ?int $ignoreId = null): bool
    {
        return OfferProduct::query()
            ->where('offer_id', $offerId)
            ->where('products_id', $productId)
            ->when(!empty($ignoreId), function ($query) use ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            })
            ->exists();
    }
}
