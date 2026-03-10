<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_address', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('address_line_1', 255);
            $table->string('address_line_2', 255)->nullable();
            $table->string('location', 150);
            $table->string('pincode', 10);
            $table->string('landmark', 255)->nullable();

            $table->boolean('is_active')->default(1);
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->dateTime('created_date')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->dateTime('updated_date')->nullable();

            $table->index(['customer_id', 'is_active'], 'idx_customer_address_customer_active');
            $table->index('created_by_id', 'idx_customer_address_created_by');
            $table->index('updated_by_id', 'idx_customer_address_updated_by');

            $table->foreign('customer_id', 'fk_customer_address_customer')
                ->references('id')
                ->on('customers')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_address');
    }
};
