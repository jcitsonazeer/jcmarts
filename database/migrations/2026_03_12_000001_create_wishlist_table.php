<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishlist', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedTinyInteger('is_active')->default(1);
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->dateTime('created_date')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->dateTime('updated_date')->nullable();

            $table->unique(['customer_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist');
    }
};
