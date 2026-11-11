<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CartDeactivationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTables();
    }

    protected function createTables(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('address_id');
            $table->decimal('sub_total', 12, 2)->default(0);
            $table->decimal('delivery_charge', 12, 2)->default(0);
            $table->decimal('packing_charge', 12, 2)->default(0);
            $table->decimal('other_charge', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('currency', 10)->default('INR');
            $table->dateTime('reservation_expires_at')->nullable();
            $table->dateTime('reservation_released_at')->nullable();
            $table->string('reservation_release_reason', 50)->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->boolean('is_active')->default(1);
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->dateTime('created_date')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->dateTime('updated_date')->nullable();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('rate_master_id');
            $table->unsignedBigInteger('cart_item_id')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->boolean('is_active')->default(1);
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->dateTime('created_date')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->dateTime('updated_date')->nullable();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('gateway', 30)->default('razorpay');
            $table->string('gateway_order_id', 100)->nullable();
            $table->string('gateway_payment_id', 100)->nullable();
            $table->string('gateway_signature', 255)->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 10)->default('INR');
            $table->string('status', 30)->default('pending');
            $table->dateTime('paid_at')->nullable();
            $table->boolean('is_active')->default(1);
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->dateTime('created_date')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->dateTime('updated_date')->nullable();
        });

        Schema::create('cart', function (Blueprint $table) {
            $table->id();
            $table->string('session_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('rate_master_id');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->boolean('is_active')->default(1);
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->dateTime('created_date')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->dateTime('updated_date')->nullable();
        });

        Schema::create('rate_master', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->boolean('is_active')->default(1);
        });
    }

    protected function makeCartRow(int $customerId, int $productId = 1, int $quantity = 2): Cart
    {
        return Cart::create([
            'session_id' => 'customer_' . $customerId,
            'product_id' => $productId,
            'rate_master_id' => 1,
            'quantity' => $quantity,
            'unit_price' => 50,
            'is_active' => 1,
            'created_date' => Carbon::now(),
        ]);
    }

    protected function makeOrder(int $customerId, float $total = 100): Order
    {
        return Order::create([
            'customer_id' => $customerId,
            'address_id' => 1,
            'sub_total' => $total,
            'delivery_charge' => 0,
            'packing_charge' => 0,
            'other_charge' => 0,
            'total_amount' => $total,
            'currency' => 'INR',
            'reservation_expires_at' => Carbon::now()->addMinutes(5),
            'is_active' => 1,
            'created_by_id' => $customerId,
            'created_date' => Carbon::now(),
        ]);
    }

    protected function makePayment(Order $order, string $status, string $gatewayOrderId = null): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'gateway' => 'razorpay',
            'gateway_order_id' => $gatewayOrderId ?? 'order_' . $order->id,
            'amount' => (float) $order->total_amount,
            'currency' => 'INR',
            'status' => $status,
            'is_active' => 1,
            'created_date' => Carbon::now(),
        ]);
    }

    public function test_successful_payment_deactivates_exact_cart_row(): void
    {
        $customerId = 1;
        $cartRow = $this->makeCartRow($customerId);

        $order = $this->makeOrder($customerId);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => 1,
            'rate_master_id' => 1,
            'cart_item_id' => $cartRow->id,
            'quantity' => 2,
            'unit_price' => 50,
            'line_total' => 100,
            'is_active' => 1,
            'created_by_id' => $customerId,
            'created_date' => Carbon::now(),
        ]);

        $this->makePayment($order, 'pending');

        $cartItems = Cart::query()->where('is_active', 1)->get();

        app(OrderService::class)->markPendingOrderAsPaid(
            'order_' . $order->id,
            $customerId,
            [
                'razorpay_payment_id' => 'pay_1',
                'razorpay_signature' => 'sig',
                'currency' => 'INR',
            ],
            $cartItems
        );

        $this->assertFalse((bool) $cartRow->fresh()->is_active);
    }

    public function test_failed_payment_leaves_cart_active(): void
    {
        $customerId = 2;
        $cartRow = $this->makeCartRow($customerId);

        $order = $this->makeOrder($customerId);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => 1,
            'rate_master_id' => 1,
            'cart_item_id' => $cartRow->id,
            'quantity' => 2,
            'unit_price' => 50,
            'line_total' => 100,
            'is_active' => 1,
            'created_by_id' => $customerId,
            'created_date' => Carbon::now(),
        ]);

        $this->makePayment($order, 'pending');

        app(OrderService::class)->releasePendingOrder($order->id, null, $customerId);

        $this->assertTrue((bool) $cartRow->fresh()->is_active);
    }

    public function test_cart_quantity_change_deactivates_original_row_only(): void
    {
        $customerId = 3;
        $originalRow = $this->makeCartRow($customerId, 1, 2);

        $order = $this->makeOrder($customerId);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => 1,
            'rate_master_id' => 1,
            'cart_item_id' => $originalRow->id,
            'quantity' => 2,
            'unit_price' => 50,
            'line_total' => 100,
            'is_active' => 1,
            'created_by_id' => $customerId,
            'created_date' => Carbon::now(),
        ]);

        $this->makePayment($order, 'pending');

        // Modify original row quantity (same row id).
        $originalRow->quantity = 5;
        $originalRow->save();

        // Re-added / new row for same product.
        $newRow = $this->makeCartRow($customerId, 1, 1);

        $cartItems = Cart::query()->where('is_active', 1)->get();

        app(OrderService::class)->markPendingOrderAsPaid(
            'order_' . $order->id,
            $customerId,
            [
                'razorpay_payment_id' => 'pay_2',
                'razorpay_signature' => 'sig',
                'currency' => 'INR',
            ],
            $cartItems
        );

        $this->assertFalse((bool) $originalRow->fresh()->is_active);
        $this->assertTrue((bool) $newRow->fresh()->is_active);
    }

    public function test_product_readdition_does_not_deactivate_new_row(): void
    {
        $customerId = 4;
        $originalRow = $this->makeCartRow($customerId, 1, 2);

        $order = $this->makeOrder($customerId);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => 1,
            'rate_master_id' => 1,
            'cart_item_id' => $originalRow->id,
            'quantity' => 2,
            'unit_price' => 50,
            'line_total' => 100,
            'is_active' => 1,
            'created_by_id' => $customerId,
            'created_date' => Carbon::now(),
        ]);

        $this->makePayment($order, 'pending');

        // Simulate removal + re-addition creating a new row.
        $originalRow->is_active = 0;
        $originalRow->save();

        $reAddedRow = $this->makeCartRow($customerId, 1, 2);

        // Restore original row to active? In real flow original row stays active until payment.
        // For this test, mark original active again to ensure ID-based deactivation targets it.
        $originalRow->is_active = 1;
        $originalRow->save();

        $cartItems = Cart::query()->where('is_active', 1)->get();

        app(OrderService::class)->markPendingOrderAsPaid(
            'order_' . $order->id,
            $customerId,
            [
                'razorpay_payment_id' => 'pay_3',
                'razorpay_signature' => 'sig',
                'currency' => 'INR',
            ],
            $cartItems
        );

        $this->assertFalse((bool) $originalRow->fresh()->is_active);
        $this->assertTrue((bool) $reAddedRow->fresh()->is_active);
    }

    public function test_duplicate_payment_callback_does_not_error(): void
    {
        $customerId = 5;
        $cartRow = $this->makeCartRow($customerId);

        $order = $this->makeOrder($customerId);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => 1,
            'rate_master_id' => 1,
            'cart_item_id' => $cartRow->id,
            'quantity' => 2,
            'unit_price' => 50,
            'line_total' => 100,
            'is_active' => 1,
            'created_by_id' => $customerId,
            'created_date' => Carbon::now(),
        ]);

        $this->makePayment($order, 'pending');

        $cartItems = Cart::query()->where('is_active', 1)->get();

        app(OrderService::class)->markPendingOrderAsPaid(
            'order_' . $order->id,
            $customerId,
            [
                'razorpay_payment_id' => 'pay_4',
                'razorpay_signature' => 'sig',
                'currency' => 'INR',
            ],
            $cartItems
        );

        // Duplicate callback.
        app(OrderService::class)->markPendingOrderAsPaid(
            'order_' . $order->id,
            $customerId,
            [
                'razorpay_payment_id' => 'pay_4',
                'razorpay_signature' => 'sig',
                'currency' => 'INR',
            ],
            $cartItems
        );

        $this->assertFalse((bool) $cartRow->fresh()->is_active);
    }

    public function test_legacy_null_cart_item_id_falls_back_to_attribute_matching(): void
    {
        $customerId = 6;
        $cartRow = $this->makeCartRow($customerId);

        $order = $this->makeOrder($customerId);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => 1,
            'rate_master_id' => 1,
            'cart_item_id' => null,
            'quantity' => 2,
            'unit_price' => 50,
            'line_total' => 100,
            'is_active' => 1,
            'created_by_id' => $customerId,
            'created_date' => Carbon::now(),
        ]);

        $this->makePayment($order, 'pending');

        $cartItems = Cart::query()->where('is_active', 1)->get();

        app(OrderService::class)->markPendingOrderAsPaid(
            'order_' . $order->id,
            $customerId,
            [
                'razorpay_payment_id' => 'pay_5',
                'razorpay_signature' => 'sig',
                'currency' => 'INR',
            ],
            $cartItems
        );

        $this->assertFalse((bool) $cartRow->fresh()->is_active);
    }
}
