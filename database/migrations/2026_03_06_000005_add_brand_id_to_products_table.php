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
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('brand_id')->nullable()->after('sub_category_id');
            $table->index('brand_id', 'idx_products_brand_id');
            $table->foreign('brand_id', 'fk_products_brand_id')
                ->references('id')
                ->on('brands')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign('fk_products_brand_id');
            $table->dropIndex('idx_products_brand_id');
            $table->dropColumn('brand_id');
        });
    }
};
