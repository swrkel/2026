<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('rcm_paddy_varieties')) {
            return;
        }

        if (! Schema::hasColumn('rcm_paddy_varieties', 'paddy_product_id')) {
            Schema::table('rcm_paddy_varieties', function (Blueprint $table) {
                $table->unsignedBigInteger('paddy_product_id')->nullable()->after('business_id');
                $table->index('paddy_product_id', 'rcm_paddy_variety_product_idx');
                $table->unique(['business_id','paddy_product_id'], 'rcm_paddy_variety_business_product_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('rcm_paddy_varieties') || ! Schema::hasColumn('rcm_paddy_varieties', 'paddy_product_id')) {
            return;
        }

        Schema::table('rcm_paddy_varieties', function (Blueprint $table) {
            $table->dropUnique('rcm_paddy_variety_business_product_unique');
            $table->dropIndex('rcm_paddy_variety_product_idx');
            $table->dropColumn('paddy_product_id');
        });
    }
};
