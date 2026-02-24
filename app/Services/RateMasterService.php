<?php

namespace App\Services;

use App\Models\Product;
use App\Models\RateMaster;
use App\Models\UomMaster;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RateMasterService
{
    public function getAll()
    {
        return RateMaster::with(['product', 'uom', 'createdBy', 'updatedBy'])
            ->orderBy('id', 'desc')
            ->get();
    }

    public function getProductsForSelectedDisplay()
    {
        return Product::select('id', 'product_name', 'product_image')
            ->whereHas('rates')
            ->withCount('rates')
            ->orderBy('product_name')
            ->get();
    }

    public function getRatesForSelectedDisplayByProduct($productId)
    {
        return RateMaster::with(['product', 'uom'])
            ->where('product_id', $productId)
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

    public function findForShow($id)
    {
        return RateMaster::with(['product', 'uom', 'createdBy', 'updatedBy'])->findOrFail($id);
    }

    public function findForEdit($id)
    {
        return RateMaster::with(['product', 'uom', 'createdBy', 'updatedBy'])->findOrFail($id);
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
            'stock_qty' => $prepared['stock_qty'],
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
                    'stock_qty' => $prepared['stock_qty'],
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
            'stock_qty' => $prepared['stock_qty'],
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
        $finalPriceInput = $data['final_price'] ?? null;
        $stockQty = (int) ($data['stock_qty'] ?? 0);

        $offerPrice = $offerPriceInput === null || $offerPriceInput === ''
            ? round(($sellingPrice * $offerPercentage) / 100, 2)
            : (float) $offerPriceInput;

        $finalPrice = $finalPriceInput === null || $finalPriceInput === ''
            ? round($sellingPrice - $offerPrice, 2)
            : (float) $finalPriceInput;

        return [
            'cost_price' => $costPrice,
            'selling_price' => $sellingPrice,
            'offer_percentage' => $offerPercentage,
            'offer_price' => $offerPrice,
            'final_price' => $finalPrice,
            'stock_qty' => $stockQty,
        ];
    }
}
