<?php

namespace App\Services;

use App\Models\Product;
use App\Models\RateMaster;
use App\Models\UomMaster;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RateMasterService
{
    public function getAll(?string $searchTerm = null)
    {
        return RateMaster::with(['product', 'uom', 'createdBy', 'updatedBy', 'latestStockInfo'])
            ->when(!empty(trim((string) $searchTerm)), function ($query) use ($searchTerm) {
                $term = trim((string) $searchTerm);

                $query->where(function ($innerQuery) use ($term) {
                    $innerQuery->whereHas('product', function ($productQuery) use ($term) {
                        $productQuery->where('product_name', 'like', '%' . $term . '%');
                    })->orWhereHas('uom', function ($uomQuery) use ($term) {
                        $uomQuery->where('primary_uom', 'like', '%' . $term . '%')
                            ->orWhere('secondary_uom', 'like', '%' . $term . '%');
                    });
                });
            })
            ->orderBy('id', 'desc')
            ->get();
    }

    public function getProductsForSelectedDisplay(?string $searchTerm = null)
    {
        return Product::select('id', 'product_name', 'product_image')
            ->whereHas('rates', function ($query) {
                $query->where('is_active', 1);
            })
            ->when(!empty(trim((string) $searchTerm)), function ($query) use ($searchTerm) {
                $query->where('product_name', 'like', '%' . trim((string) $searchTerm) . '%');
            })
            ->withCount([
                'rates' => function ($query) {
                    $query->where('is_active', 1);
                },
            ])
            ->orderBy('product_name')
            ->get();
    }

    public function getRatesForSelectedDisplayByProduct($productId)
    {
        return RateMaster::with(['product', 'uom', 'latestStockInfo'])
            ->where('product_id', $productId)
            ->where('is_active', 1)
            ->orderBy('id')
            ->get();
    }

    public function updateSelectedDisplayForProduct($productId, $rateId, $adminId)
    {
        return DB::transaction(function () use ($productId, $rateId, $adminId) {
            $rate = RateMaster::where('product_id', $productId)
                ->where('id', $rateId)
                ->firstOrFail();

            RateMaster::where('product_id', $productId)->update([
                'selected_display' => 0,
                'updated_by_id' => $adminId,
                'updated_date' => Carbon::now(),
            ]);

            $rate->update([
                'selected_display' => 1,
                'updated_by_id' => $adminId,
                'updated_date' => Carbon::now(),
            ]);

            return $rate;
        });
    }

    public function getProductsForDropdown()
    {
        return Product::orderBy('product_name')->get();
    }

    public function searchProductOptions(?string $searchTerm = null, int $limit = 10)
    {
        return Product::query()
            ->when(!empty(trim((string) $searchTerm)), function ($query) use ($searchTerm) {
                $query->where('product_name', 'like', '%' . trim((string) $searchTerm) . '%');
            })
            ->orderBy('product_name')
            ->limit($limit)
            ->get(['id', 'product_name'])
            ->map(function (Product $product) {
                return [
                    'id' => (int) $product->id,
                    'label' => (string) $product->product_name,
                ];
            });
    }

    public function getProductOption(int $productId): ?array
    {
        $product = Product::query()->find($productId, ['id', 'product_name']);

        if (!$product) {
            return null;
        }

        return [
            'id' => (int) $product->id,
            'label' => (string) $product->product_name,
        ];
    }

    public function resolveProductIdFromInput(?string $searchTerm): ?int
    {
        $searchTerm = trim((string) $searchTerm);

        if ($searchTerm === '') {
            return null;
        }

        $exactMatch = Product::query()
            ->whereRaw('LOWER(product_name) = ?', [mb_strtolower($searchTerm)])
            ->value('id');

        if ($exactMatch) {
            return (int) $exactMatch;
        }

        $matches = Product::query()
            ->where('product_name', 'like', '%' . $searchTerm . '%')
            ->orderBy('product_name')
            ->limit(2)
            ->pluck('id');

        if ($matches->count() === 1) {
            return (int) $matches->first();
        }

        return null;
    }

    public function getUomsForDropdown()
    {
        return UomMaster::orderBy('primary_uom')->get();
    }

    public function getPrimaryUomsForCreate()
    {
        return UomMaster::select('primary_uom')
            ->whereNotNull('primary_uom')
            ->where('primary_uom', '!=', '')
            ->distinct()
            ->orderBy('primary_uom')
            ->pluck('primary_uom');
    }

    public function getSecondaryUomsByPrimaryForCreate()
    {
        return UomMaster::select('id', 'primary_uom', 'secondary_uom')
            ->orderBy('primary_uom')
            ->orderBy('secondary_uom')
            ->get()
            ->groupBy('primary_uom')
            ->map(function ($items) {
                return $items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'secondary_uom' => $item->secondary_uom,
                    ];
                })->values();
            });
    }

    public function getUomIdsByPrimaryUom($primaryUom)
    {
        return UomMaster::where('primary_uom', $primaryUom)
            ->pluck('id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->toArray();
    }

    public function getExistingUomIdsForProduct($productId, array $uomIds)
    {
        if (empty($uomIds)) {
            return [];
        }

        return RateMaster::where('product_id', $productId)
            ->whereIn('uom_id', $uomIds)
            ->pluck('uom_id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->toArray();
    }

    public function getExistingRatesForProduct($productId, array $uomIds)
    {
        if (empty($uomIds)) {
            return [];
        }

        return RateMaster::query()
            ->where('product_id', $productId)
            ->whereIn('uom_id', $uomIds)
            ->get([
                'uom_id',
                'cost_price',
                'selling_price',
                'offer_percentage',
                'offer_price',
                'final_price',
                'soldout_status',
                'stock_dependent',
                'is_active',
            ])
            ->keyBy(function (RateMaster $rate) {
                return (int) $rate->uom_id;
            })
            ->map(function (RateMaster $rate) {
                return [
                    'uom_id' => (int) $rate->uom_id,
                    'cost_price' => (string) $rate->cost_price,
                    'selling_price' => (string) $rate->selling_price,
                    'offer_percentage' => (string) $rate->offer_percentage,
                    'offer_price' => (string) $rate->offer_price,
                    'final_price' => (string) $rate->final_price,
                    'soldout_status' => (string) $rate->soldout_status,
                    'stock_dependent' => (string) $rate->stock_dependent,
                    'is_active' => (int) $rate->is_active,
                ];
            })
            ->all();
    }

    public function findForShow($id)
    {
        return RateMaster::with(['product', 'uom', 'createdBy', 'updatedBy', 'latestStockInfo'])->findOrFail($id);
    }

    public function findForEdit($id)
    {
        return RateMaster::with(['product', 'uom', 'createdBy', 'updatedBy', 'latestStockInfo'])->findOrFail($id);
    }

    public function create($data, $adminId)
    {
        $prepared = $this->prepareRateData($data);

        return RateMaster::create([
            'product_id' => $data['product_id'],
            'uom_id' => $data['uom_id'],
            'cost_price' => $prepared['cost_price'],
            'selling_price' => $prepared['selling_price'],
            'offer_percentage' => $prepared['offer_percentage'],
            'offer_price' => $prepared['offer_price'],
            'final_price' => $prepared['final_price'],
            'soldout_status' => $data['soldout_status'] ?? 'NO',
            'stock_dependent' => $data['stock_dependent'] ?? 'NO',
            'is_active' => array_key_exists('is_active', $data) ? (int) $data['is_active'] : 1,
            'selected_display' => 0,
            'created_by_id' => $adminId,
            'created_date' => Carbon::now(),
        ]);
    }

    public function createMultiple($productId, array $rows, $adminId)
    {
        return DB::transaction(function () use ($productId, $rows, $adminId) {
            $createdRows = [];

            foreach ($rows as $row) {
                $prepared = $this->prepareRateData($row);

                $createdRows[] = RateMaster::create([
                    'product_id' => $productId,
                    'uom_id' => $row['uom_id'],
                    'cost_price' => $prepared['cost_price'],
                    'selling_price' => $prepared['selling_price'],
                    'offer_percentage' => $prepared['offer_percentage'],
                    'offer_price' => $prepared['offer_price'],
                    'final_price' => $prepared['final_price'],
                    'soldout_status' => $row['soldout_status'] ?? 'NO',
                    'stock_dependent' => $row['stock_dependent'] ?? 'NO',
                    'is_active' => array_key_exists('is_active', $row) ? (int) $row['is_active'] : 1,
                    'selected_display' => 0,
                    'created_by_id' => $adminId,
                    'created_date' => Carbon::now(),
                ]);
            }

            return $createdRows;
        });
    }

    public function update($id, $data, $adminId)
    {
        $rate = RateMaster::findOrFail($id);
        $prepared = $this->prepareRateData($data);

        $rate->update([
            'product_id' => $data['product_id'],
            'uom_id' => $data['uom_id'],
            'cost_price' => $prepared['cost_price'],
            'selling_price' => $prepared['selling_price'],
            'offer_percentage' => $prepared['offer_percentage'],
            'offer_price' => $prepared['offer_price'],
            'final_price' => $prepared['final_price'],
            'soldout_status' => $data['soldout_status'] ?? $rate->soldout_status ?? 'NO',
            'stock_dependent' => $data['stock_dependent'] ?? $rate->stock_dependent ?? 'NO',
            'is_active' => array_key_exists('is_active', $data) ? (int) $data['is_active'] : 1,
            'updated_by_id' => $adminId,
            'updated_date' => Carbon::now(),
        ]);

        return $rate;
    }

    public function delete($id)
    {
        $rate = RateMaster::findOrFail($id);
        $rate->delete();
    }

    private function prepareRateData($data)
    {
        $costPrice = (float) ($data['cost_price'] ?? 0);
        $sellingPrice = (float) ($data['selling_price'] ?? 0);
        $offerPercentage = (float) ($data['offer_percentage'] ?? 0);
        $offerPriceInput = $data['offer_price'] ?? null;

        $offerPrice = $offerPriceInput === null || $offerPriceInput === ''
            ? round(($sellingPrice * $offerPercentage) / 100, 2)
            : (float) $offerPriceInput;

        $offerPrice = max(0, min($offerPrice, $sellingPrice));
        $finalPrice = round(max($sellingPrice - $offerPrice, 0), 2);

        return [
            'cost_price' => $costPrice,
            'selling_price' => $sellingPrice,
            'offer_percentage' => $offerPercentage,
            'offer_price' => $offerPrice,
            'final_price' => $finalPrice,
        ];
    }
}
