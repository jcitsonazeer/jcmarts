<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('orders')
            && Schema::hasTable('payments')
            && Schema::hasColumn('orders', 'razorpay_order_id')
        ) {
            DB::statement("
                INSERT INTO payments (
                    order_id,
                    customer_id,
                    gateway,
                    gateway_order_id,
                    gateway_payment_id,
                    gateway_signature,
                    amount,
                    currency,
                    status,
                    paid_at,
                    is_active,
                    created_by_id,
                    created_date,
                    updated_by_id,
                    updated_date
                )
                SELECT
                    orders.id,
                    orders.customer_id,
                    'razorpay',
                    orders.razorpay_order_id,
                    orders.razorpay_payment_id,
                    orders.razorpay_signature,
                    orders.total_amount,
                    orders.currency,
                    CASE
                        WHEN orders.payment_status = 'paid' THEN 'paid'
                        WHEN orders.payment_status = 'pending' THEN 'pending'
                        ELSE orders.payment_status
                    END,
                    orders.paid_at,
                    orders.is_active,
                    orders.created_by_id,
                    orders.created_date,
                    orders.updated_by_id,
                    orders.updated_date
                FROM orders
                LEFT JOIN payments
                    ON payments.order_id = orders.id
                    AND payments.gateway = 'razorpay'
                    AND payments.gateway_order_id = orders.razorpay_order_id
                WHERE orders.razorpay_order_id IS NOT NULL
                    AND payments.id IS NULL
            ");
        }

        if (Schema::hasTable('orders')) {
            $this->dropIndexIfExists('orders', 'idx_orders_rzp_order');
            $this->dropIndexIfExists('orders', 'idx_orders_rzp_payment');

            Schema::table('orders', function (Blueprint $table) {
                $columns = [];

                foreach (['razorpay_order_id', 'razorpay_payment_id', 'razorpay_signature'] as $column) {
                    if (Schema::hasColumn('orders', $column)) {
                        $columns[] = $column;
                    }
                }

                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'razorpay_order_id')) {
                $table->string('razorpay_order_id', 100)->nullable()->after('payment_status');
            }

            if (!Schema::hasColumn('orders', 'razorpay_payment_id')) {
                $table->string('razorpay_payment_id', 100)->nullable()->after('razorpay_order_id');
            }

            if (!Schema::hasColumn('orders', 'razorpay_signature')) {
                $table->string('razorpay_signature', 255)->nullable()->after('razorpay_payment_id');
            }
        });

        if (Schema::hasTable('orders') && Schema::hasTable('payments')) {
            DB::statement("
                UPDATE orders
                INNER JOIN payments ON payments.order_id = orders.id
                SET
                    orders.razorpay_order_id = payments.gateway_order_id,
                    orders.razorpay_payment_id = payments.gateway_payment_id,
                    orders.razorpay_signature = payments.gateway_signature
                WHERE payments.gateway = 'razorpay'
                    AND payments.is_active = 1
            ");
        }
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        $exists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();

        if ($exists) {
            DB::statement("ALTER TABLE {$table} DROP INDEX {$index}");
        }
    }
};
