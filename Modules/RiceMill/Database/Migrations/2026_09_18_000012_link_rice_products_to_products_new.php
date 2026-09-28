<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('rcm_products')) {
            return;
        }

        if (! Schema::hasColumn('rcm_products', 'products_new_product_id')) {
            Schema::table('rcm_products', function (Blueprint $table) {
                // Despite the historical column name, this stores the exact
                // Product ID used by the live Products New module: products.id.
                $table->unsignedBigInteger('products_new_product_id')->nullable()->after('business_id');
                $table->index('products_new_product_id', 'rcm_products_products_new_product_idx');
                $table->unique(['business_id','products_new_product_id'], 'rcm_products_business_products_new_unique');
            });
        }

        // Products New's current Product module uses the tenant `products`
        // table. Repair/backfill Rice Mill links by exact Business + Name + SKU.
        if (Schema::hasTable('products')) {
            DB::statement("\n                UPDATE rcm_products rp\n                LEFT JOIN products linked\n                    ON linked.id = rp.products_new_product_id\n                   AND linked.business_id = rp.business_id\n                   AND TRIM(linked.name) = TRIM(rp.name)\n                   AND TRIM(COALESCE(linked.sku,'')) = TRIM(COALESCE(rp.code,''))\n                SET rp.products_new_product_id = NULL\n                WHERE rp.products_new_product_id IS NOT NULL\n                  AND linked.id IS NULL\n            ");

            DB::statement("\n                UPDATE rcm_products rp\n                INNER JOIN products p\n                    ON p.business_id = rp.business_id\n                   AND TRIM(p.name) = TRIM(rp.name)\n                   AND TRIM(COALESCE(p.sku,'')) = TRIM(COALESCE(rp.code,''))\n                LEFT JOIN rcm_products duplicate_link\n                    ON duplicate_link.business_id = rp.business_id\n                   AND duplicate_link.products_new_product_id = p.id\n                   AND duplicate_link.id <> rp.id\n                SET rp.products_new_product_id = p.id\n                WHERE rp.products_new_product_id IS NULL\n                  AND duplicate_link.id IS NULL\n            ");
        }

        // paddy_product_id is also the exact Product ID used by Products New
        // (products.id). Repair any values written by the previous interim build.
        if (Schema::hasTable('rcm_paddy_varieties')
            && Schema::hasColumn('rcm_paddy_varieties', 'paddy_product_id')
            && Schema::hasTable('products')) {
            DB::statement("\n                UPDATE rcm_paddy_varieties pv\n                LEFT JOIN products linked\n                    ON linked.id = pv.paddy_product_id\n                   AND linked.business_id = pv.business_id\n                   AND TRIM(linked.name) = TRIM(pv.name)\n                   AND TRIM(COALESCE(linked.sku,'')) = TRIM(COALESCE(pv.code,''))\n                SET pv.paddy_product_id = NULL\n                WHERE pv.paddy_product_id IS NOT NULL\n                  AND linked.id IS NULL\n            ");

            DB::statement("\n                UPDATE rcm_paddy_varieties pv\n                INNER JOIN products p\n                    ON p.business_id = pv.business_id\n                   AND TRIM(p.name) = TRIM(pv.name)\n                   AND TRIM(COALESCE(p.sku,'')) = TRIM(COALESCE(pv.code,''))\n                LEFT JOIN rcm_paddy_varieties duplicate_link\n                    ON duplicate_link.business_id = pv.business_id\n                   AND duplicate_link.paddy_product_id = p.id\n                   AND duplicate_link.id <> pv.id\n                SET pv.paddy_product_id = p.id\n                WHERE pv.paddy_product_id IS NULL\n                  AND duplicate_link.id IS NULL\n            ");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('rcm_products') || ! Schema::hasColumn('rcm_products', 'products_new_product_id')) {
            return;
        }

        Schema::table('rcm_products', function (Blueprint $table) {
            $table->dropUnique('rcm_products_business_products_new_unique');
            $table->dropIndex('rcm_products_products_new_product_idx');
            $table->dropColumn('products_new_product_id');
        });
    }
};
