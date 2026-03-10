<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rate_master', function (Blueprint $table) {
            $table->string('soldout_status', 3)->default('NO')->after('final_price');
            $table->string('stock_dependent', 3)->default('NO')->after('soldout_status');
            $table->dropColumn('stock_qty');
        });
    }

    public function down(): void
    {
        Schema::table('rate_master', function (Blueprint $table) {
            $table->integer('stock_qty')->default(0)->after('final_price');
            $table->dropColumn(['soldout_status', 'stock_dependent']);
        });
    }
};
