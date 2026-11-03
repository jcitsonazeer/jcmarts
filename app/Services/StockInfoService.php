<?php

namespace App\Services;

use App\Models\RateMaster;
use App\Models\StockInfo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StockInfoService
{
    public function getRateOptions()
    {
        return RateMaster::with(['product', 'uom', 'latestStockInfo'])
            ->where('stock_dependent', 'YES')
            ->orderByDesc('id')
            ->get()
            ->map(function ($rate) {
                return $this->formatRateOption($rate);
            });
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
            ->map(function ($rate) {
                return $this->formatRateOption($rate);
            });
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
        $rate = RateMaster::find($rateMasterId);

        if ($rate && $rate->getAttribute('available_stock') !== null) {
            return (int) $rate->available_stock;
        }

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

        $updateData = [
            'soldout_status' => $newStock > 0 ? 'NO' : 'YES',
            'updated_by_id' => $adminId,
            'updated_date' => Carbon::now(),
        ];

        if (Schema::hasColumn('rate_master', 'available_stock')) {
            $updateData['available_stock'] = $newStock;
        }

        RateMaster::query()
            ->where('id', $rateMasterId)
            ->update($updateData);

        return $stockInfo;
    }

    public function reserveStock($rateMasterId, $quantity, ?int $orderId = null, ?int $userId = null): StockInfo
    {
        $rateMasterId = (int) $rateMasterId;

        if ($orderId !== null) {
            $existingReservation = StockInfo::query()
                ->where('rate_master_id', $rateMasterId)
                ->where('sale_order_id', $orderId)
                ->where('sale_quantity', $quantity)
                ->first();

            if ($existingReservation) {
                return $existingReservation;
            }
        }

        if (!is_numeric($quantity) || $quantity <= 0) {
            throw new \RuntimeException('Quantity must be greater than zero.');
        }

        if (!Schema::hasColumn('rate_master', 'available_stock')) {
            $latestStock = StockInfo::query()
                ->where('rate_master_id', $rateMasterId)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $currentStock = $latestStock ? (int) $latestStock->current_stock : 0;

            if ($currentStock < $quantity) {
                throw new \RuntimeException('Insufficient stock available.');
            }

            $newStock = $currentStock - $quantity;

            RateMaster::query()
                ->where('id', $rateMasterId)
                ->update([
                    'soldout_status' => $newStock > 0 ? 'NO' : 'YES',
                    'updated_by_id' => $userId,
                    'updated_date' => Carbon::now(),
                ]);

            return StockInfo::create([
                'rate_master_id' => $rateMasterId,
                'stock_in_count' => 0,
                'sale_quantity' => $quantity,
                'current_stock' => $newStock,
                'sale_order_id' => $orderId,
                'is_active' => 1,
                'created_by_id' => $userId,
                'created_date' => Carbon::now(),
            ]);
        }

        $updated = DB::table('rate_master')
            ->where('id', $rateMasterId)
            ->where('available_stock', '>=', $quantity)
            ->decrement('available_stock', $quantity);

        if ($updated !== 1) {
            throw new \RuntimeException('Insufficient stock available.');
        }

        $newStock = DB::table('rate_master')
            ->where('id', $rateMasterId)
            ->value('available_stock');

        $newStock = is_null($newStock) ? 0 : $newStock;

        RateMaster::query()
            ->where('id', $rateMasterId)
            ->update([
                'soldout_status' => $newStock > 0 ? 'NO' : 'YES',
                'updated_by_id' => $userId,
                'updated_date' => Carbon::now(),
            ]);

        return StockInfo::create([
            'rate_master_id' => $rateMasterId,
            'stock_in_count' => 0,
            'sale_quantity' => $quantity,
            'current_stock' => $newStock,
            'sale_order_id' => $orderId,
            'is_active' => 1,
            'created_by_id' => $userId,
            'created_date' => Carbon::now(),
        ]);
    }

    public function releaseStock($rateMasterId, $quantity, ?int $orderId = null, ?int $userId = null): StockInfo
    {
        $rateMasterId = (int) $rateMasterId;

        if ($orderId !== null) {
            $existingRelease = StockInfo::query()
                ->where('rate_master_id', $rateMasterId)
                ->where('sale_order_id', $orderId)
                ->where('stock_in_count', $quantity)
                ->first();

            if ($existingRelease) {
                return $existingRelease;
            }
        }

        if (!is_numeric($quantity) || $quantity <= 0) {
            throw new \RuntimeException('Quantity must be greater than zero.');
        }

        if (!Schema::hasColumn('rate_master', 'available_stock')) {
            $latestStock = StockInfo::query()
                ->where('rate_master_id', $rateMasterId)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $currentStock = $latestStock ? (int) $latestStock->current_stock : 0;
            $newStock = $currentStock + $quantity;

            RateMaster::query()
                ->where('id', $rateMasterId)
                ->update([
                    'soldout_status' => $newStock > 0 ? 'NO' : 'YES',
                    'updated_by_id' => $userId,
                    'updated_date' => Carbon::now(),
                ]);

            return StockInfo::create([
                'rate_master_id' => $rateMasterId,
                'stock_in_count' => $quantity,
                'sale_quantity' => 0,
                'current_stock' => $newStock,
                'sale_order_id' => $orderId,
                'is_active' => 1,
                'created_by_id' => $userId,
                'created_date' => Carbon::now(),
            ]);
        }

        DB::table('rate_master')
            ->where('id', $rateMasterId)
            ->increment('available_stock', $quantity);

        $newStock = DB::table('rate_master')
            ->where('id', $rateMasterId)
            ->value('available_stock');

        $newStock = is_null($newStock) ? $quantity : $newStock;

        RateMaster::query()
            ->where('id', $rateMasterId)
            ->update([
                'soldout_status' => $newStock > 0 ? 'NO' : 'YES',
                'updated_by_id' => $userId,
                'updated_date' => Carbon::now(),
            ]);

        return StockInfo::create([
            'rate_master_id' => $rateMasterId,
            'stock_in_count' => $quantity,
            'sale_quantity' => 0,
            'current_stock' => $newStock,
            'sale_order_id' => $orderId,
            'is_active' => 1,
            'created_by_id' => $userId,
            'created_date' => Carbon::now(),
        ]);
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