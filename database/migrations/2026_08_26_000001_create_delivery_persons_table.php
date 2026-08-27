<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_persons', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('mobile', 15)->unique();
            $table->string('email', 255)->nullable()->unique();
            $table->string('password', 255);
            $table->string('vehicle_type', 50)->nullable();
            $table->string('vehicle_number', 20)->nullable();
            $table->string('status', 20)->default('active');
            $table->string('availability_status', 20)->default('offline');
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->dateTime('created_date')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->dateTime('updated_date')->nullable();

            $table->index('status');
            $table->index('availability_status');
            $table->index('created_by_id', 'idx_dp_created_by');
            $table->index('updated_by_id', 'idx_dp_updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_persons');
    }
};
