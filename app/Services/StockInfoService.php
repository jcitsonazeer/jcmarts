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
            ->map(fn ($rate) => $this->formatRateOption($rate));
    }

    public function searchRateOptions(string $term, int $limit = 10)
    {
        $trimmed = trim($term);

        $query = RateMaster::with(['product', 'uom', 'latestStockInfo'])
            ->where('stock_dependent', 'YES');

        if ($trimmed !== '') {
            $query->where(function ($builder) use ($trimmed) {
                $builder->where('id', $trimmed)
                    ->orWhereHas('product', function ($productQuery) use ($trimmed) {
                        $productQuery->where('product_name', 'like', '%' . $trimmed . '%');
                    })
                    ->orWhereHas('uom', function ($uomQuery) use ($trimmed) {
                        $uomQuery->where('primary_uom', 'like', '%' . $trimmed . '%')
                            ->orWhere('secondary_uom', 'like', '%' . $trimmed . '%');
                    });
            });
        }

        return $query->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn ($rate) => $this->formatRateOption($rate));
    }

    public function getRateOption(int $rateMasterId): ?array
    {
        $rate = RateMaster::with(['product', 'uom', 'latestStockInfo'])
            ->where('stock_dependent', 'YES')
            ->find($rateMasterId);

        return $rate ? $this->formatRateOption($rate) : null;
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

        $stockInfo = StockInfo::create([
            'rate_master_id' => $rateMasterId,
            'stock_in_count' => $stockInCount,
            'sale_quantity' => 0,
            'current_stock' => $newStock,
            'sale_order_id' => null,
            'is_active' => 1,
            'created_by_id' => $adminId,
            'created_date' => Carbon::now(),
        ]);

        RateMaster::query()
            ->where('id', $rateMasterId)
            ->update([
                'soldout_status' => $newStock > 0 ? 'NO' : 'YES',
                'updated_by_id' => $adminId,
                'updated_date' => Carbon::now(),
            ]);

        return $stockInfo;
    }

    public function getHistoryByRate(int $rateMasterId)
    {
        return StockInfo::query()
            ->select([
                'id',
                'rate_master_id',
                'stock_in_count',
                'sale_quantity',
                'current_stock',
                'sale_order_id',
                'created_date',
            ])
            ->with(['rate.product', 'rate.uom'])
            ->where('rate_master_id', $rateMasterId)
            ->orderByDesc('id')
            ->paginate(20);
    }

    private function formatRateOption(RateMaster $rate): array
    {
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
    }
}
