<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->index();
            $table->foreignId('customer_id')->index();
            $table->string('gateway', 30)->default('razorpay');
            $table->string('gateway_order_id', 100)->nullable()->index();
            $table->string('gateway_payment_id', 100)->nullable()->index();
            $table->string('gateway_signature', 255)->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 10)->default('INR');
            $table->string('status', 30)->default('paid')->index();
            $table->dateTime('paid_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->dateTime('created_date')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->dateTime('updated_date')->nullable();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->index();
            $table->foreignId('order_id')->index();
            $table->foreignId('customer_id')->index();
            $table->string('gateway', 30)->default('razorpay');
            $table->string('gateway_refund_id', 100)->nullable()->index();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 10)->default('INR');
            $table->string('status', 30)->default('requested')->index();
            $table->string('reason', 255)->nullable();
            $table->unsignedBigInteger('requested_by_id')->nullable();
            $table->dateTime('requested_at')->nullable();
            $table->unsignedBigInteger('approved_by_id')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->text('failed_reason')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->dateTime('created_date')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->dateTime('updated_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payments');
    }
};
