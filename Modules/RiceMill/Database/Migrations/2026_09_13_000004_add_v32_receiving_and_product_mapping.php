<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('rcm_products') && ! Schema::hasColumn('rcm_products', 'paddy_variety_id')) {
            Schema::table('rcm_products', function (Blueprint $table) {
                $table->unsignedBigInteger('paddy_variety_id')->nullable()->after('rice_type');
                $table->index(['business_id', 'paddy_variety_id'], 'rcm_products_business_paddy_variety_idx');
            });
        }

        if (Schema::hasTable('rcm_paddy_receipts') && ! Schema::hasColumn('rcm_paddy_receipts', 'foreign_matter_limit_percent')) {
            Schema::table('rcm_paddy_receipts', function (Blueprint $table) {
                $table->decimal('foreign_matter_limit_percent', 8, 3)->nullable()->after('foreign_matter_percent');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rcm_paddy_receipts') && Schema::hasColumn('rcm_paddy_receipts', 'foreign_matter_limit_percent')) {
            Schema::table('rcm_paddy_receipts', function (Blueprint $table) {
                $table->dropColumn('foreign_matter_limit_percent');
            });
        }

        if (Schema::hasTable('rcm_products') && Schema::hasColumn('rcm_products', 'paddy_variety_id')) {
            Schema::table('rcm_products', function (Blueprint $table) {
                $table->dropIndex('rcm_products_business_paddy_variety_idx');
                $table->dropColumn('paddy_variety_id');
            });
        }
    }
};
