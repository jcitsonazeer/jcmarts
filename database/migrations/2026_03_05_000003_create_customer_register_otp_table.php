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
        Schema::create('customer_register_otp', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name', 120);
            $table->string('mobile_number', 15);
            $table->string('otp_code', 6);
            $table->dateTime('otp_expires_at');

            // Common project columns
            $table->boolean('is_active')->default(1);
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->dateTime('created_date')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->dateTime('updated_date')->nullable();

            $table->index(['mobile_number', 'is_active'], 'idx_customer_register_otp_mobile_active');
            $table->index('otp_expires_at', 'idx_customer_register_otp_expires');
            $table->index('created_by_id', 'idx_customer_register_otp_created_by');
            $table->index('updated_by_id', 'idx_customer_register_otp_updated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_register_otp');
    }
};
