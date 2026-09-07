<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('promo_tile_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('promo_tile_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->dateTime('created_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo_tile_products');
    }
};