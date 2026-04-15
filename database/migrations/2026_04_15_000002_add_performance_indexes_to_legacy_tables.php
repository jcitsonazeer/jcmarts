<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('products', ['product_name'], 'idx_products_product_name');
        $this->addIndexIfMissing('products', ['sub_category_id'], 'idx_products_sub_category_id');
        $this->addIndexIfMissing('products', ['is_active'], 'idx_products_is_active');

        $this->addIndexIfMissing('sub_category', ['category_id'], 'idx_sub_category_category_id');
        $this->addIndexIfMissing('sub_category', ['sub_category_name'], 'idx_sub_category_name');

        $this->addIndexIfMissing('category', ['category_name'], 'idx_category_name');

        $this->addIndexIfMissing('rate_master', ['product_id'], 'idx_rate_master_product_id');
        $this->addIndexIfMissing('rate_master', ['uom_id'], 'idx_rate_master_uom_id');
        $this->addIndexIfMissing('rate_master', ['is_active'], 'idx_rate_master_is_active');
        $this->addIndexIfMissing('rate_master', ['stock_dependent'], 'idx_rate_master_stock_dependent');
        $this->addIndexIfMissing('rate_master', ['product_id', 'is_active'], 'idx_rate_master_product_active');

        $this->addIndexIfMissing('offer_products', ['offer_id'], 'idx_offer_products_offer_id');
        $this->addIndexIfMissing('offer_products', ['products_id'], 'idx_offer_products_product_id');
        $this->addIndexIfMissing('offer_products', ['offer_id', 'products_id'], 'idx_offer_products_offer_product');

        $this->addIndexIfMissing('index_banner', ['sub_category_id'], 'idx_index_banner_sub_category_id');
        $this->addIndexIfMissing('index_banner', ['offer_details_id'], 'idx_index_banner_offer_details_id');

        $this->addIndexIfMissing('uom_master', ['primary_uom'], 'idx_uom_master_primary_uom');
        $this->addIndexIfMissing('offer_details', ['is_active'], 'idx_offer_details_is_active');
        $this->addIndexIfMissing('offer_details', ['offer_name'], 'idx_offer_details_offer_name');
        $this->addIndexIfMissing('cart', ['product_id'], 'idx_cart_product_id');
        $this->addIndexIfMissing('cart', ['rate_master_id'], 'idx_cart_rate_master_id');
        $this->addIndexIfMissing('wishlist', ['product_id'], 'idx_wishlist_product_id');
    }

    public function down(): void
    {
        $this->dropIndexIfExists('products', 'idx_products_product_name');
        $this->dropIndexIfExists('products', 'idx_products_sub_category_id');
        $this->dropIndexIfExists('products', 'idx_products_is_active');

        $this->dropIndexIfExists('sub_category', 'idx_sub_category_category_id');
        $this->dropIndexIfExists('sub_category', 'idx_sub_category_name');

        $this->dropIndexIfExists('category', 'idx_category_name');

        $this->dropIndexIfExists('rate_master', 'idx_rate_master_product_id');
        $this->dropIndexIfExists('rate_master', 'idx_rate_master_uom_id');
        $this->dropIndexIfExists('rate_master', 'idx_rate_master_is_active');
        $this->dropIndexIfExists('rate_master', 'idx_rate_master_stock_dependent');
        $this->dropIndexIfExists('rate_master', 'idx_rate_master_product_active');

        $this->dropIndexIfExists('offer_products', 'idx_offer_products_offer_id');
        $this->dropIndexIfExists('offer_products', 'idx_offer_products_product_id');
        $this->dropIndexIfExists('offer_products', 'idx_offer_products_offer_product');

        $this->dropIndexIfExists('index_banner', 'idx_index_banner_sub_category_id');
        $this->dropIndexIfExists('index_banner', 'idx_index_banner_offer_details_id');

        $this->dropIndexIfExists('uom_master', 'idx_uom_master_primary_uom');
        $this->dropIndexIfExists('offer_details', 'idx_offer_details_is_active');
        $this->dropIndexIfExists('offer_details', 'idx_offer_details_offer_name');
        $this->dropIndexIfExists('cart', 'idx_cart_product_id');
        $this->dropIndexIfExists('cart', 'idx_cart_rate_master_id');
        $this->dropIndexIfExists('wishlist', 'idx_wishlist_product_id');
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
