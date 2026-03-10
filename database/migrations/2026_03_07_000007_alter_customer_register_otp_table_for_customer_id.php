<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_register_otp', function (Blueprint $table) {
            if (Schema::hasColumn('customer_register_otp', 'customer_name')) {
                $table->dropColumn('customer_name');
            }

            if (Schema::hasColumn('customer_register_otp', 'mobile_number')) {
                $table->dropColumn('mobile_number');
            }
        });

        Schema::table('customer_register_otp', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_register_otp', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->after('id');
            }
        });

        Schema::table('customer_register_otp', function (Blueprint $table) {
            if (!$this->indexExists('customer_register_otp', 'idx_customer_register_otp_customer_active')) {
                $table->index(['customer_id', 'is_active'], 'idx_customer_register_otp_customer_active');
            }

            if (!$this->foreignKeyExists('customer_register_otp', 'fk_customer_register_otp_customer')) {
                $table->foreign('customer_id', 'fk_customer_register_otp_customer')
                    ->references('id')
                    ->on('customers')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_register_otp', function (Blueprint $table) {
            if (Schema::hasColumn('customer_register_otp', 'customer_id')) {
                if ($this->foreignKeyExists('customer_register_otp', 'fk_customer_register_otp_customer')) {
                    $table->dropForeign('fk_customer_register_otp_customer');
                }

                if ($this->indexExists('customer_register_otp', 'idx_customer_register_otp_customer_active')) {
                    $table->dropIndex('idx_customer_register_otp_customer_active');
                }

                $table->dropColumn('customer_id');
            }

            if (!Schema::hasColumn('customer_register_otp', 'customer_name')) {
                $table->string('customer_name', 120)->nullable();
            }

            if (!Schema::hasColumn('customer_register_otp', 'mobile_number')) {
                $table->string('mobile_number', 15)->nullable();
                $table->index(['mobile_number', 'is_active'], 'idx_customer_register_otp_mobile_active');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $databaseName = DB::connection()->getDatabaseName();

        $result = DB::selectOne(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$databaseName, $table, $indexName]
        );

        return $result !== null;
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        $databaseName = DB::connection()->getDatabaseName();

        $result = DB::selectOne(
            'SELECT 1 FROM information_schema.table_constraints WHERE constraint_schema = ? AND table_name = ? AND constraint_name = ? AND constraint_type = ? LIMIT 1',
            [$databaseName, $table, $constraintName, 'FOREIGN KEY']
        );

        return $result !== null;
    }
};
