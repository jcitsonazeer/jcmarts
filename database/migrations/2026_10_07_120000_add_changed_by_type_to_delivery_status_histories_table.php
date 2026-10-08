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
        Schema::table('delivery_status_histories', function (Blueprint $table) {
            // Records who made the change: 'admin' or 'delivery_person'.
            // Nullable so old rows keep working without changing their values.
            $table->string('changed_by_type', 30)->nullable()->after('changed_by_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_status_histories', function (Blueprint $table) {
            $table->dropColumn('changed_by_type');
        });
    }
};
