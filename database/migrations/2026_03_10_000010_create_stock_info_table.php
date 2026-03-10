<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_info', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rate_master_id');
            $table->integer('stock_in_count')->default(0);
            $table->integer('sale_quantity')->default(0);
            $table->integer('current_stock')->default(0);
            $table->unsignedBigInteger('sale_order_id')->nullable();

            $table->boolean('is_active')->default(1);
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->dateTime('created_date')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->dateTime('updated_date')->nullable();

            $table->index('rate_master_id', 'idx_stock_info_rate_master');
            $table->index('sale_order_id', 'idx_stock_info_sale_order');
            $table->index('is_active', 'idx_stock_info_is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_info');
    }
};
