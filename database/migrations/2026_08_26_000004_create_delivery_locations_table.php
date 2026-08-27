<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_id');
            $table->unsignedBigInteger('delivery_person_id');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->dateTime('recorded_at');
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->dateTime('created_date')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->dateTime('updated_date')->nullable();

            $table->index('delivery_id', 'idx_dl_delivery');
            $table->index('delivery_person_id', 'idx_dl_delivery_person');
            $table->index('recorded_at', 'idx_dl_recorded_at');
            $table->index('created_by_id', 'idx_dl_created_by');
            $table->index('updated_by_id', 'idx_dl_updated_by');

            $table->foreign('delivery_id')
                ->references('id')
                ->on('deliveries')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('delivery_person_id')
                ->references('id')
                ->on('delivery_persons')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_locations');
    }
};
