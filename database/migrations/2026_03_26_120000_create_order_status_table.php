<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_status', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('order_status', 50);
            $table->dateTime('action_time');
            $table->unsignedBigInteger('action_done_by_id')->nullable();

            $table->index('order_id', 'idx_order_status_order');
            $table->index('order_status', 'idx_order_status_status');
            $table->index('action_time', 'idx_order_status_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status');
    }
};
