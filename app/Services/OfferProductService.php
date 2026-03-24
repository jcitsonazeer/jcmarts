<?php

namespace App\Services;

use App\Models\OfferDetail;
use App\Models\OfferProduct;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Str;

class OfferProductService
{
    public function getAll(?string $searchTerm = null)
    {
        return OfferProduct::with(['offer', 'product', 'createdBy', 'updatedBy'])
            ->when(!empty(trim((string) $searchTerm)), function ($query) use ($searchTerm) {
                $term = trim((string) $searchTerm);

                $query->where(function ($innerQuery) use ($term) {
                    $innerQuery->whereHas('offer', function ($offerQuery) use ($term) {
                        $offerQuery->where('offer_name', 'like', '%' . $term . '%');
                    })->orWhereHas('product', function ($productQuery) use ($term) {
                        $productQuery->where('product_name', 'like', '%' . $term . '%');
                    });
                });
            })
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

    public function findProductIdByName(string $productName): ?int
    {
        $productName = trim($productName);

        if ($productName === '') {
            return null;
        }

        $product = Product::query()
            ->where('is_active', 1)
            ->whereRaw('LOWER(product_name) = ?', [Str::lower($productName)])
            ->first(['id']);

        return $product ? (int) $product->id : null;
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
