<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dateTime('reservation_expires_at')->nullable()->after('paid_at');
            $table->dateTime('reservation_released_at')->nullable()->after('reservation_expires_at');
            $table->string('reservation_release_reason', 50)->nullable()->after('reservation_released_at');

            $table->index('reservation_expires_at', 'idx_orders_reservation_expires');
            $table->index('reservation_released_at', 'idx_orders_reservation_released');
            $table->index('reservation_release_reason', 'idx_orders_reservation_reason');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_reservation_expires');
            $table->dropIndex('idx_orders_reservation_released');
            $table->dropIndex('idx_orders_reservation_reason');
            $table->dropColumn([
                'reservation_expires_at',
                'reservation_released_at',
                'reservation_release_reason',
            ]);
        });
    }
};
