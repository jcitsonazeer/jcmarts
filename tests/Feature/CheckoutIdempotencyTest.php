<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CheckoutIdempotencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createCheckoutTables();
    }

    protected function createCheckoutTables(): void
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

    protected function makePayment(Order $order, string $status): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'gateway' => 'razorpay',
            'gateway_order_id' => 'order_'.$order->id,
            'amount' => (float) $order->total_amount,
            'currency' => 'INR',
            'status' => $status,
            'is_active' => 1,
            'created_date' => Carbon::now(),
        ]);
    }

    public function test_it_releases_every_open_pending_order_for_a_customer(): void
    {
        $customerId = 7;

        $firstOrder = $this->makeOrder($customerId);
        $secondOrder = $this->makeOrder($customerId);
        $pendingPayment = $this->makePayment($secondOrder, 'pending');

        app(OrderService::class)->releaseOpenPendingOrdersForCustomer($customerId);

        $firstOrder = $firstOrder->fresh();
        $secondOrder = $secondOrder->fresh();
        $pendingPayment = $pendingPayment->fresh();

        $this->assertFalse((bool) $firstOrder->is_active);
        $this->assertNotNull($firstOrder->reservation_released_at);
        $this->assertSame('customer_cancelled', $firstOrder->reservation_release_reason);

        $this->assertFalse((bool) $secondOrder->is_active);
        $this->assertNotNull($secondOrder->reservation_released_at);
        $this->assertSame('customer_cancelled', $secondOrder->reservation_release_reason);
        $this->assertSame('failed', $pendingPayment->status);
    }

    public function test_it_keeps_orders_that_have_a_successful_payment(): void
    {
        $customerId = 8;

        $order = $this->makeOrder($customerId);
        $this->makePayment($order, 'paid');

        app(OrderService::class)->releaseOpenPendingOrdersForCustomer($customerId);

        $order = $order->fresh();

        $this->assertTrue((bool) $order->is_active);
        $this->assertNull($order->reservation_released_at);
    }

    public function test_it_finds_an_active_order_by_razorpay_order_id(): void
    {
        $customerId = 9;

        $order = $this->makeOrder($customerId);
        $this->makePayment($order, 'pending');

        $foundOrder = app(OrderService::class)
            ->getActiveOrderByRazorpayOrderId('order_'.$order->id, $customerId);

        $this->assertNotNull($foundOrder);
        $this->assertSame($order->id, $foundOrder->id);
    }
}
