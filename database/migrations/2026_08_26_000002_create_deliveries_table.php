<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('delivery_person_id')->nullable();
            $table->string('status', 30)->default('not_assigned');
            $table->dateTime('assigned_at')->nullable();
            $table->dateTime('accepted_at')->nullable();
            $table->dateTime('picked_up_at')->nullable();
            $table->dateTime('out_for_delivery_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->text('delivery_address')->nullable();
            $table->decimal('delivery_latitude', 10, 8)->nullable();
            $table->decimal('delivery_longitude', 11, 8)->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->dateTime('created_date')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->dateTime('updated_date')->nullable();

            $table->index('order_id', 'idx_deliveries_order');
            $table->index('delivery_person_id', 'idx_deliveries_delivery_person');
            $table->index('status', 'idx_deliveries_status');
            $table->index('created_by_id', 'idx_deliveries_created_by');
            $table->index('updated_by_id', 'idx_deliveries_updated_by');

            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->foreign('delivery_person_id')
                ->references('id')
                ->on('delivery_persons')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
