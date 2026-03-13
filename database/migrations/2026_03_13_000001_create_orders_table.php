<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
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
            $table->string('payment_method', 30)->default('razorpay');
            $table->string('payment_status', 20)->default('paid');
            $table->string('razorpay_order_id', 100)->nullable();
            $table->string('razorpay_payment_id', 100)->nullable();
            $table->string('razorpay_signature', 255)->nullable();
            $table->dateTime('paid_at')->nullable();

            $table->boolean('is_active')->default(1);
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->dateTime('created_date')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->dateTime('updated_date')->nullable();

            $table->index('customer_id', 'idx_orders_customer');
            $table->index('payment_status', 'idx_orders_payment_status');
            $table->index('razorpay_order_id', 'idx_orders_rzp_order');
            $table->index('razorpay_payment_id', 'idx_orders_rzp_payment');
            $table->index('is_active', 'idx_orders_is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
