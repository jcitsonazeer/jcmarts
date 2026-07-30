<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orders') && Schema::hasTable('payments') && Schema::hasColumn('orders', 'payment_status')) {
            DB::statement("
                INSERT INTO payments (
                    order_id,
                    customer_id,
                    gateway,
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
                    COALESCE(orders.payment_method, 'razorpay'),
                    orders.total_amount,
                    orders.currency,
                    orders.payment_status,
                    orders.paid_at,
                    orders.is_active,
                    orders.created_by_id,
                    orders.created_date,
                    orders.updated_by_id,
                    orders.updated_date
                FROM orders
                LEFT JOIN payments ON payments.order_id = orders.id
                WHERE payments.id IS NULL
            ");
        }

        if (Schema::hasTable('orders')) {
            $this->dropIndexIfExists('orders', 'idx_orders_payment_status');

            Schema::table('orders', function (Blueprint $table) {
                $columns = [];

                foreach (['payment_method', 'payment_status', 'paid_at'] as $column) {
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
            if (!Schema::hasColumn('orders', 'payment_method')) {
                $table->string('payment_method', 30)->default('razorpay')->after('currency');
            }

            if (!Schema::hasColumn('orders', 'payment_status')) {
                $table->string('payment_status', 20)->default('paid')->after('payment_method');
            }

            if (!Schema::hasColumn('orders', 'paid_at')) {
                $table->dateTime('paid_at')->nullable()->after('payment_status');
            }
        });

        if (Schema::hasTable('orders') && Schema::hasTable('payments')) {
            DB::statement("
                UPDATE orders
                INNER JOIN payments ON payments.order_id = orders.id
                SET
                    orders.payment_method = payments.gateway,
                    orders.payment_status = payments.status,
                    orders.paid_at = payments.paid_at
                WHERE payments.is_active = 1
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
