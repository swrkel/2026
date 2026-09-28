<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('supplier_product_mappings')) {
            return;
        }

        $addBusinessId = ! Schema::hasColumn('supplier_product_mappings', 'business_id');
        $addSupplierSku = ! Schema::hasColumn('supplier_product_mappings', 'supplier_sku');

        if ($addBusinessId || $addSupplierSku) {
            Schema::table('supplier_product_mappings', function (Blueprint $table) use ($addBusinessId, $addSupplierSku): void {
                if ($addBusinessId) {
                    $table->unsignedBigInteger('business_id')->nullable()->after('id');
                }

                if ($addSupplierSku) {
                    $table->string('supplier_sku', 191)->nullable()->after('product_id');
                }
            });
        }

        DB::statement("
            UPDATE supplier_product_mappings AS spm
            INNER JOIN contacts AS c ON c.id = spm.supplier_id
            SET spm.business_id = c.business_id
            WHERE spm.business_id IS NULL OR spm.business_id = 0
        ");

        if (! $this->indexExists('supplier_product_mappings', 'spm_business_id_index')) {
            Schema::table('supplier_product_mappings', function (Blueprint $table): void {
                $table->index('business_id', 'spm_business_id_index');
            });
        }

        if (! $this->indexExists('supplier_product_mappings', 'spm_business_supplier_product_index')) {
            Schema::table('supplier_product_mappings', function (Blueprint $table): void {
                $table->index(
                    ['business_id', 'supplier_id', 'product_id'],
                    'spm_business_supplier_product_index'
                );
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('supplier_product_mappings')) {
            return;
        }

        $dropCompositeIndex = $this->indexExists('supplier_product_mappings', 'spm_business_supplier_product_index');
        $dropBusinessIndex = $this->indexExists('supplier_product_mappings', 'spm_business_id_index');
        $dropSupplierSku = Schema::hasColumn('supplier_product_mappings', 'supplier_sku');
        $dropBusinessId = Schema::hasColumn('supplier_product_mappings', 'business_id');

        Schema::table('supplier_product_mappings', function (Blueprint $table) use (
            $dropCompositeIndex,
            $dropBusinessIndex,
            $dropSupplierSku,
            $dropBusinessId
        ): void {
            if ($dropCompositeIndex) {
                $table->dropIndex('spm_business_supplier_product_index');
            }

            if ($dropBusinessIndex) {
                $table->dropIndex('spm_business_id_index');
            }

            if ($dropSupplierSku) {
                $table->dropColumn('supplier_sku');
            }

            if ($dropBusinessId) {
                $table->dropColumn('business_id');
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $result = DB::selectOne(
            'SELECT COUNT(*) AS aggregate FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$table, $index]
        );

        return (int) ($result->aggregate ?? 0) > 0;
    }
};
