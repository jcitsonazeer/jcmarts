<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_status_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_id');
            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30);
            $table->unsignedBigInteger('changed_by_id')->nullable();
            $table->dateTime('changed_at');
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->dateTime('created_date')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->dateTime('updated_date')->nullable();

            $table->index('delivery_id', 'idx_dsh_delivery');
            $table->index('new_status', 'idx_dsh_new_status');
            $table->index('changed_at', 'idx_dsh_changed_at');
            $table->index('created_by_id', 'idx_dsh_created_by');
            $table->index('updated_by_id', 'idx_dsh_updated_by');

            $table->foreign('delivery_id')
                ->references('id')
                ->on('deliveries')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_status_histories');
    }
};
