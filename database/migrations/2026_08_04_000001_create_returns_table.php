<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'delivered_at')) {
                $table->dateTime('delivered_at')->nullable()->after('reservation_release_reason')->index();
            }
        });

        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->index();
            $table->foreignId('customer_id')->index();
            $table->string('reason', 100);
            $table->text('customer_note')->nullable();
            $table->string('status', 50)->default('return_requested')->index();
            $table->decimal('refund_amount', 12, 2)->default(0);
            $table->boolean('sellable_stock')->nullable();
            $table->text('admin_note')->nullable();
            $table->dateTime('requested_at')->nullable();
            $table->unsignedBigInteger('approved_by_id')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->dateTime('pickup_scheduled_at')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->dateTime('inspected_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->dateTime('created_date')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->dateTime('updated_date')->nullable();
        });

        Schema::table('refunds', function (Blueprint $table) {
            if (!Schema::hasColumn('refunds', 'return_id')) {
                $table->foreignId('return_id')->nullable()->after('id')->index();
            }
        });

        DB::table('orders')
            ->join('order_status', 'orders.id', '=', 'order_status.order_id')
            ->where('order_status.order_status', 'order_delivered')
            ->whereNull('orders.delivered_at')
            ->update(['orders.delivered_at' => DB::raw('order_status.action_time')]);
    }

    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            if (Schema::hasColumn('refunds', 'return_id')) {
                $table->dropColumn('return_id');
            }
        });

        Schema::dropIfExists('returns');

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'delivered_at')) {
                $table->dropColumn('delivered_at');
            }
        });
    }
};
