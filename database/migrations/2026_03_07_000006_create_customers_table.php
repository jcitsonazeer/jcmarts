<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('mobile_number', 15)->unique('uq_customers_mobile_number');
            $table->string('verified_status', 20)->default('pending');

            $table->boolean('is_active')->default(1);
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->dateTime('created_date')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->dateTime('updated_date')->nullable();

            $table->index(['mobile_number', 'is_active'], 'idx_customers_mobile_active');
            $table->index('verified_status', 'idx_customers_verified_status');
            $table->index('created_by_id', 'idx_customers_created_by');
            $table->index('updated_by_id', 'idx_customers_updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};

