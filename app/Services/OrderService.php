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
}
