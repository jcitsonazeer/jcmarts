<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('cart', ['session_id', 'is_active'], 'idx_cart_session_active');
        $this->addIndexIfMissing('cart', ['session_id', 'product_id', 'rate_master_id', 'is_active'], 'idx_cart_session_product_rate_active');
        $this->addIndexIfMissing('wishlist', ['customer_id', 'is_active'], 'idx_wishlist_customer_active');
    }

    public function down(): void
    {
        $this->dropIndexIfExists('cart', 'idx_cart_session_active');
        $this->dropIndexIfExists('cart', 'idx_cart_session_product_rate_active');
        $this->dropIndexIfExists('wishlist', 'idx_wishlist_customer_active');
    }

    private function addIndexIfMissing(string $table, array $columns, string $indexName): void
    {
        if (!Schema::hasTable($table) || $this->indexExists($table, $columns, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
            $blueprint->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (!Schema::hasTable($table) || !$this->indexNameExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
            $blueprint->dropIndex($indexName);
        });
    }

    private function indexExists(string $table, array $columns, string $indexName): bool
    {
        if ($this->indexNameExists($table, $indexName)) {
            return true;
        }

        $normalizedColumns = array_values($columns);

        foreach ($this->getTableIndexes($table) as $existingColumns) {
            if ($existingColumns === $normalizedColumns) {
                return true;
            }
        }

        return false;
    }

    private function indexNameExists(string $table, string $indexName): bool
    {
        return array_key_exists($indexName, $this->getTableIndexes($table));
    }

    private function getTableIndexes(string $table): array
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            return $this->getMysqlIndexes($table);
        }

        if ($driver === 'sqlite') {
            return $this->getSqliteIndexes($table);
        }

        if ($driver === 'pgsql') {
            return $this->getPostgresIndexes($table);
        }

        return [];
    }

    private function getMysqlIndexes(string $table): array
    {
        $database = DB::getDatabaseName();
        $rows = DB::table('information_schema.statistics')
            ->select('index_name', 'column_name', 'seq_in_index')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->orderBy('index_name')
            ->orderBy('seq_in_index')
            ->get();

        $indexes = [];

        foreach ($rows as $row) {
            $indexes[$row->index_name][] = $row->column_name;
        }

        return $indexes;
    }

    private function getSqliteIndexes(string $table): array
    {
        $indexes = [];
        $indexList = DB::select("PRAGMA index_list('$table')");

        foreach ($indexList as $indexRow) {
            $indexName = $indexRow->name ?? null;

            if (!$indexName) {
                continue;
            }

            $indexInfo = DB::select("PRAGMA index_info('$indexName')");
            $indexes[$indexName] = array_values(array_filter(array_map(
                fn ($columnRow) => $columnRow->name ?? null,
                $indexInfo
            )));
        }

        return $indexes;
    }

    private function getPostgresIndexes(string $table): array
    {
        $rows = DB::select(
            <<<'SQL'
            SELECT
                i.relname AS index_name,
                a.attname AS column_name,
                array_position(ix.indkey, a.attnum) AS seq_in_index
            FROM pg_class t
            JOIN pg_index ix ON t.oid = ix.indrelid
            JOIN pg_class i ON i.oid = ix.indexrelid
            JOIN pg_namespace n ON n.oid = t.relnamespace
            JOIN pg_attribute a ON a.attrelid = t.oid AND a.attnum = ANY(ix.indkey)
            WHERE n.nspname = current_schema()
              AND t.relname = ?
            ORDER BY i.relname, seq_in_index
            SQL,
            [$table]
        );

        $indexes = [];

        foreach ($rows as $row) {
            $indexes[$row->index_name][] = $row->column_name;
        }

        return $indexes;
    }
};
