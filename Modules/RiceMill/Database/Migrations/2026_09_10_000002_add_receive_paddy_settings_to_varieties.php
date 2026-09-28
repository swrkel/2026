<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('rcm_paddy_varieties')) {
            return;
        }

        Schema::table('rcm_paddy_varieties', function (Blueprint $table) {
            if (!Schema::hasColumn('rcm_paddy_varieties','default_moisture_percent')) {
                $table->decimal('default_moisture_percent',8,3)->nullable()->after('name');
            }
            if (!Schema::hasColumn('rcm_paddy_varieties','foreign_matter_limit_percent')) {
                $table->decimal('foreign_matter_limit_percent',8,3)->nullable()->after('default_moisture_percent');
            }
            if (!Schema::hasColumn('rcm_paddy_varieties','expected_rice_yield_percent')) {
                $table->decimal('expected_rice_yield_percent',8,3)->nullable()->after('foreign_matter_limit_percent');
            }
            if (!Schema::hasColumn('rcm_paddy_varieties','expected_broken_rice_percent')) {
                $table->decimal('expected_broken_rice_percent',8,3)->nullable()->after('expected_rice_yield_percent');
            }
            if (!Schema::hasColumn('rcm_paddy_varieties','expected_bran_percent')) {
                $table->decimal('expected_bran_percent',8,3)->nullable()->after('expected_broken_rice_percent');
            }
            if (!Schema::hasColumn('rcm_paddy_varieties','expected_husk_percent')) {
                $table->decimal('expected_husk_percent',8,3)->nullable()->after('expected_bran_percent');
            }
            if (!Schema::hasColumn('rcm_paddy_varieties','expected_process_loss_percent')) {
                $table->decimal('expected_process_loss_percent',8,3)->nullable()->after('expected_husk_percent');
            }
            if (!Schema::hasColumn('rcm_paddy_varieties','quality_grade')) {
                $table->string('quality_grade',50)->nullable()->after('expected_process_loss_percent');
            }
            if (!Schema::hasColumn('rcm_paddy_varieties','lot_opening_number')) {
                $table->unsignedBigInteger('lot_opening_number')->default(1)->after('quality_grade');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('rcm_paddy_varieties')) {
            return;
        }
        $columns = [
            'default_moisture_percent','foreign_matter_limit_percent','expected_rice_yield_percent',
            'expected_broken_rice_percent','expected_bran_percent','expected_husk_percent',
            'expected_process_loss_percent','quality_grade','lot_opening_number'
        ];
        $existing = array_values(array_filter($columns, fn($c)=>Schema::hasColumn('rcm_paddy_varieties',$c)));
        if ($existing) {
            Schema::table('rcm_paddy_varieties', fn(Blueprint $table)=>$table->dropColumn($existing));
        }
    }
};
