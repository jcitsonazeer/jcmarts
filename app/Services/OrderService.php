<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RateMaster;
use App\Models\StockInfo;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderService
{
    private const PAYMENT_RESERVATION_MINUTES = 10;

    public function assertStockAvailable(Collection $cartItems): void
    {
        foreach ($cartItems as $item) {
            if (!$item->rate) {
                continue;
            }

            if ($item->rate->stock_dependent !== 'YES') {
                continue;
            }

            $currentStock = StockInfo::query()
                ->where('rate_master_id', (int) $item->rate_master_id)
                ->orderByDesc('id')
                ->value('current_stock');

            $currentStock = (int) ($currentStock ?? 0);

            if ($currentStock < (int) $item->quantity) {
                $productName = $item->product ? $item->product->product_name : 'Selected item';
                throw new RuntimeException($productName . ' is out of stock.');
            }
        }
    }

    public function cleanupExpiredPendingOrders(): void
    {
        $expiredOrderIds = $this->getExpiredPendingOrdersQuery()->pluck('id');

        foreach ($expiredOrderIds as $orderId) {
            $this->releasePendingOrder((int) $orderId);
        }
    }

    public function getExpiredPendingOrders(): Collection
    {
        return $this->getExpiredPendingOrdersQuery()
            ->with([
                'customer',
                'address',
                'items.product',
                'items.rate.uom',
            ])
            ->orderBy('created_date')
            ->orderBy('id')
            ->get();
    }

    public function createPendingOrderFromCart(
        int $customerId,
        int $addressId,
        array $orderSummary,
        Collection $cartItems
    ): Order {
        return DB::transaction(function () use ($customerId, $addressId, $orderSummary, $cartItems) {
            $stockSnapshots = [];

            foreach ($cartItems as $item) {
                $rate = $item->rate ?: RateMaster::find((int) $item->rate_master_id);

                if (!$rate || $rate->stock_dependent !== 'YES') {
                    continue;
                }

                $currentStock = $this->getLockedCurrentStock((int) $item->rate_master_id);

                if ($currentStock < (int) $item->quantity) {
                    $productName = $item->product ? $item->product->product_name : 'Selected item';
                    throw new RuntimeException($productName . ' is out of stock.');
                }

                $stockSnapshots[(int) $item->rate_master_id] = $currentStock;
            }

            $order = Order::create([
                'customer_id' => $customerId,
                'address_id' => $addressId,
                'sub_total' => (float) $orderSummary['sub_total'],
                'delivery_charge' => (float) $orderSummary['delivery_charge'],
                'packing_charge' => (float) $orderSummary['packing_charge'],
                'other_charge' => (float) $orderSummary['other_charge'],
                'total_amount' => (float) $orderSummary['total'],
                'currency' => 'INR',
                'payment_method' => 'razorpay',
                'payment_status' => 'pending',
                'is_active' => 1,
                'created_by_id' => $customerId,
                'created_date' => Carbon::now(),
            ]);

            foreach ($cartItems as $item) {
                $quantity = (int) $item->quantity;
                $unitPrice = (float) $item->unit_price;
                $lineTotal = $quantity * $unitPrice;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => (int) $item->product_id,
                    'rate_master_id' => (int) $item->rate_master_id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'is_active' => 1,
                    'created_by_id' => $customerId,
                    'created_date' => Carbon::now(),
                ]);

                $rate = $item->rate ?: RateMaster::find((int) $item->rate_master_id);

                if (!$rate || $rate->stock_dependent !== 'YES') {
                    continue;
                }

                $currentStock = (int) ($stockSnapshots[(int) $item->rate_master_id] ?? 0);
                $newStock = $currentStock - $quantity;
                $stockSnapshots[(int) $item->rate_master_id] = $newStock;

                StockInfo::create([
                    'rate_master_id' => (int) $item->rate_master_id,
                    'stock_in_count' => 0,
                    'sale_quantity' => $quantity,
                    'current_stock' => $newStock,
                    'sale_order_id' => $order->id,
                    'is_active' => 1,
                    'created_by_id' => $customerId,
                    'created_date' => Carbon::now(),
                ]);

                $this->syncSoldOutStatus((int) $item->rate_master_id, $newStock, $customerId);
            }

            return $order->load('items');
        });
    }

    public function attachRazorpayOrder(int $orderId, string $razorpayOrderId, ?int $customerId = null): Order
    {
        $order = Order::query()->findOrFail($orderId);

        $order->razorpay_order_id = $razorpayOrderId;
        $order->updated_by_id = $customerId;
        $order->updated_date = Carbon::now();
        $order->save();

        return $order;
    }

    public function markPendingOrderAsPaid(
        string $razorpayOrderId,
        int $customerId,
        array $razorpayPayload,
        ?Collection $cartItems = null
    ): Order {
        return DB::transaction(function () use ($razorpayOrderId, $customerId, $razorpayPayload, $cartItems) {
            $order = Order::query()
                ->where('razorpay_order_id', $razorpayOrderId)
                ->where('customer_id', $customerId)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                throw new RuntimeException('Reserved order not found.');
            }

            if ($order->payment_status === 'paid') {
                return $order->load('items');
            }

            if ($order->payment_status !== 'pending') {
                throw new RuntimeException('Order is no longer available for payment.');
            }

            $order->currency = (string) ($razorpayPayload['currency'] ?? $order->currency ?? 'INR');
            $order->payment_status = 'paid';
            $order->razorpay_payment_id = (string) $razorpayPayload['razorpay_payment_id'];
            $order->razorpay_signature = (string) $razorpayPayload['razorpay_signature'];
            $order->paid_at = Carbon::now();
            $order->updated_by_id = $customerId;
            $order->updated_date = Carbon::now();
            $order->save();

            if ($cartItems) {
                $this->deactivatePurchasedCartItems($cartItems, $order->items, $customerId);
            }

            return $order->load('items');
        });
    }

    public function releasePendingOrder(?int $orderId = null, ?string $razorpayOrderId = null, ?int $customerId = null): void
    {
        if (!$orderId && !$razorpayOrderId) {
            return;
        }

        DB::transaction(function () use ($orderId, $razorpayOrderId, $customerId) {
            $query = Order::query()->with('items');

            if ($orderId) {
                $query->where('id', $orderId);
            } else {
                $query->where('razorpay_order_id', $razorpayOrderId);
            }

            if ($customerId) {
                $query->where('customer_id', $customerId);
            }

            $order = $query->lockForUpdate()->first();

            if (!$order || $order->payment_status !== 'pending') {
                return;
            }

            foreach ($order->items as $item) {
                $rate = RateMaster::find((int) $item->rate_master_id);

                if (!$rate || $rate->stock_dependent !== 'YES') {
                    continue;
                }

                $currentStock = $this->getLockedCurrentStock((int) $item->rate_master_id);
                $newStock = $currentStock + (int) $item->quantity;

                StockInfo::create([
                    'rate_master_id' => (int) $item->rate_master_id,
                    'stock_in_count' => (int) $item->quantity,
                    'sale_quantity' => 0,
                    'current_stock' => $newStock,
                    'sale_order_id' => $order->id,
                    'is_active' => 1,
                    'created_by_id' => $customerId ?? $order->customer_id,
                    'created_date' => Carbon::now(),
                ]);

                $this->syncSoldOutStatus((int) $item->rate_master_id, $newStock, $customerId ?? $order->customer_id);
            }

            $order->payment_status = 'failed';
            $order->is_active = 0;
            $order->updated_by_id = $customerId ?? $order->customer_id;
            $order->updated_date = Carbon::now();
            $order->save();
        });
    }

    public function createPaidOrderFromCart(
        int $customerId,
        int $addressId,
        array $orderSummary,
        Collection $cartItems,
        array $razorpayPayload
    ): Order {
        return DB::transaction(function () use ($customerId, $addressId, $orderSummary, $cartItems, $razorpayPayload) {
            $order = Order::create([
                'customer_id' => $customerId,
                'address_id' => $addressId,
                'sub_total' => (float) $orderSummary['sub_total'],
                'delivery_charge' => (float) $orderSummary['delivery_charge'],
                'packing_charge' => (float) $orderSummary['packing_charge'],
                'other_charge' => (float) $orderSummary['other_charge'],
                'total_amount' => (float) $orderSummary['total'],
                'currency' => (string) ($razorpayPayload['currency'] ?? 'INR'),
                'payment_method' => 'razorpay',
                'payment_status' => 'paid',
                'razorpay_order_id' => (string) $razorpayPayload['razorpay_order_id'],
                'razorpay_payment_id' => (string) $razorpayPayload['razorpay_payment_id'],
                'razorpay_signature' => (string) $razorpayPayload['razorpay_signature'],
                'paid_at' => Carbon::now(),
                'is_active' => 1,
                'created_by_id' => $customerId,
                'created_date' => Carbon::now(),
            ]);

            foreach ($cartItems as $item) {
                $quantity = (int) $item->quantity;
                $unitPrice = (float) $item->unit_price;
                $lineTotal = $quantity * $unitPrice;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => (int) $item->product_id,
                    'rate_master_id' => (int) $item->rate_master_id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'is_active' => 1,
                    'created_by_id' => $customerId,
                    'created_date' => Carbon::now(),
                ]);

                $rate = $item->rate ?: RateMaster::find((int) $item->rate_master_id);

                if ($rate && $rate->stock_dependent === 'YES') {
                    $currentStock = StockInfo::query()
                        ->where('rate_master_id', (int) $item->rate_master_id)
                        ->orderByDesc('id')
                        ->value('current_stock');

                    $currentStock = (int) ($currentStock ?? 0);
                    $newStock = $currentStock - $quantity;

                    if ($newStock < 0) {
                        throw new RuntimeException('Insufficient stock for order item.');
                    }

                    StockInfo::create([
                        'rate_master_id' => (int) $item->rate_master_id,
                        'stock_in_count' => 0,
                        'sale_quantity' => $quantity,
                        'current_stock' => $newStock,
                        'sale_order_id' => $order->id,
                        'is_active' => 1,
                        'created_by_id' => $customerId,
                        'created_date' => Carbon::now(),
                    ]);
                }
            }

            foreach ($cartItems as $item) {
                $item->is_active = 0;
                $item->updated_by_id = $customerId;
                $item->updated_date = Carbon::now();
                $item->save();
            }

            return $order;
        });
    }

    private function getLockedCurrentStock(int $rateMasterId): int
    {
        $latestStock = StockInfo::query()
            ->where('rate_master_id', $rateMasterId)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        return (int) ($latestStock?->current_stock ?? 0);
    }

    private function syncSoldOutStatus(int $rateMasterId, int $currentStock, ?int $userId = null): void
    {
        RateMaster::query()
            ->where('id', $rateMasterId)
            ->update([
                'soldout_status' => $currentStock > 0 ? 'NO' : 'YES',
                'updated_by_id' => $userId,
                'updated_date' => Carbon::now(),
            ]);
    }

    private function deactivatePurchasedCartItems(Collection $cartItems, Collection $orderItems, int $customerId): void
    {
        $orderItemsByRate = $orderItems->keyBy(fn (OrderItem $item) => (int) $item->rate_master_id);

        foreach ($cartItems as $cartItem) {
            $reservedItem = $orderItemsByRate->get((int) $cartItem->rate_master_id);

            if (!$reservedItem) {
                continue;
            }

            if ((int) $reservedItem->product_id !== (int) $cartItem->product_id) {
                continue;
            }

            if ((int) $reservedItem->quantity !== (int) $cartItem->quantity) {
                continue;
            }

            $cartItem->is_active = 0;
            $cartItem->updated_by_id = $customerId;
            $cartItem->updated_date = Carbon::now();
            $cartItem->save();
        }
    }

    private function getExpiredPendingOrdersQuery()
    {
        return Order::query()
            ->where('payment_status', 'pending')
            ->where('is_active', 1)
            ->whereNotNull('created_date')
            ->where('created_date', '<=', Carbon::now()->subMinutes(self::PAYMENT_RESERVATION_MINUTES));
    }
}
