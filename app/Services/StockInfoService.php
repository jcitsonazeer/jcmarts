<?php

namespace App\Services;

use App\Models\RateMaster;
use App\Models\StockInfo;
use Carbon\Carbon;

class StockInfoService
{
    public function getRateOptions()
    {
        return RateMaster::with(['product', 'uom', 'latestStockInfo'])
            ->where('stock_dependent', 'YES')
            ->orderByDesc('id')
            ->get()
            ->map(function ($rate) {
                $labelParts = [];

                if ($rate->product) {
                    $labelParts[] = $rate->product->product_name;
                }

                if ($rate->uom) {
                    $labelParts[] = trim(($rate->uom->secondary_uom ?? '') . ' ' . ($rate->uom->primary_uom ?? ''));
                }

                $label = trim(implode(' - ', array_filter($labelParts)));

                return [
                    'id' => $rate->id,
                    'label' => $label !== '' ? $label : 'Rate ' . $rate->id,
                    'cost_price' => $rate->cost_price,
                    'selling_price' => $rate->selling_price,
                    'offer_percentage' => $rate->offer_percentage,
                    'offer_price' => $rate->offer_price,
                    'final_price' => $rate->final_price,
                    'current_stock' => $rate->latestStockInfo ? $rate->latestStockInfo->current_stock : 0,
                ];
            });
    }

    public function findRateDetails(int $rateMasterId)
    {
        return RateMaster::with(['product', 'uom', 'latestStockInfo'])
            ->where('stock_dependent', 'YES')
            ->find($rateMasterId);
    }

    public function getLatestCurrentStock(int $rateMasterId): int
    {
        $current = StockInfo::query()
            ->where('rate_master_id', $rateMasterId)
            ->orderByDesc('id')
            ->value('current_stock');

        return (int) ($current ?? 0);
    }

    public function createStockIn(int $rateMasterId, int $stockInCount, ?int $adminId = null): StockInfo
    {
        $currentStock = $this->getLatestCurrentStock($rateMasterId);
        $newStock = $currentStock + max(0, $stockInCount);

        return StockInfo::create([
            'rate_master_id' => $rateMasterId,
            'stock_in_count' => $stockInCount,
            'sale_quantity' => 0,
            'current_stock' => $newStock,
            'sale_order_id' => null,
            'is_active' => 1,
            'created_by_id' => $adminId,
            'created_date' => Carbon::now(),
        ]);
    }
}
