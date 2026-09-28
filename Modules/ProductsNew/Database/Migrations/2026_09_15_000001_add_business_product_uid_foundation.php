<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Additive only. No existing key, relation, product ID or stock row is
        // replaced. Nullable columns keep legacy tenants/application versions safe.
        if (Schema::hasTable('products') && ! Schema::hasColumn('products', 'product_uid')) {
            Schema::table('products', function (Blueprint $table) {
                $table->char('product_uid', 36)->nullable()->after('id');
            });
        }

        if (Schema::hasTable('variations') && ! Schema::hasColumn('variations', 'variation_uid')) {
            Schema::table('variations', function (Blueprint $table) {
                $table->char('variation_uid', 36)->nullable()->after('id');
            });
        }

        // UNIQUE permits multiple NULL values in MySQL, so this can safely be
        // enabled before a controlled backfill. Existing rows are not modified.
        $this->ensureUniqueIndex('products', 'products_product_uid_unique', 'product_uid');
        $this->ensureUniqueIndex('variations', 'variations_variation_uid_unique', 'variation_uid');
    }

    public function down(): void
    {
        // Rollback only removes the UID foundation introduced by this migration;
        // legacy product IDs/stock relationships remain untouched.
        $this->dropIndexIfExists('variations', 'variations_variation_uid_unique');
        if (Schema::hasTable('variations') && Schema::hasColumn('variations', 'variation_uid')) {
            Schema::table('variations', function (Blueprint $table) {
                $table->dropColumn('variation_uid');
            });
        }

        $this->dropIndexIfExists('products', 'products_product_uid_unique');
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'product_uid')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('product_uid');
            });
        }
    }

    protected function ensureUniqueIndex(string $table, string $index, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column) || $this->indexExists($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index, $column) {
            $blueprint->unique($column, $index);
        });
    }

    protected function dropIndexIfExists(string $table, string $index): void
    {
        if (! Schema::hasTable($table) || ! $this->indexExists($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index) {
            $blueprint->dropUnique($index);
        });
    }

    protected function indexExists(string $table, string $index): bool
    {
        try {
            $rows = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]);
            return count($rows) > 0;
        } catch (\Throwable $exception) {
            return false;
        }
    }
};
