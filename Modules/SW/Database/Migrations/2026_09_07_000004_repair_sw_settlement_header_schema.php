<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bring older tenant sw_settlements headers up to the schema expected by the
 * current SW Settlement screen without replacing or rewriting existing rows.
 *
 * Older tenants can already have sw_settlements from an earlier SW build. In
 * that case the original CREATE migration is recorded as executed, so adding a
 * new column to that old CREATE block does not alter the tenant table. IS2205
 * exposed that drift for pump_operator_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sw_settlements')) {
            // The base SW migration owns table creation. If it has genuinely
            // never run, do not create a partial settlement table here.
            return;
        }

        $missing = [
            'pump_operator_id' => ! Schema::hasColumn('sw_settlements', 'pump_operator_id'),
            'cash_denomination' => ! Schema::hasColumn('sw_settlements', 'cash_denomination'),
            'total_sales' => ! Schema::hasColumn('sw_settlements', 'total_sales'),
            'total_collected' => ! Schema::hasColumn('sw_settlements', 'total_collected'),
            'variance' => ! Schema::hasColumn('sw_settlements', 'variance'),
            'is_edited' => ! Schema::hasColumn('sw_settlements', 'is_edited'),
            'updated_by' => ! Schema::hasColumn('sw_settlements', 'updated_by'),
            'deleted_at' => ! Schema::hasColumn('sw_settlements', 'deleted_at'),
        ];

        if (! in_array(true, $missing, true)) {
            return;
        }

        Schema::table('sw_settlements', function (Blueprint $table) use ($missing) {
            if ($missing['pump_operator_id']) {
                $table->unsignedInteger('pump_operator_id')->nullable();
            }
            if ($missing['cash_denomination']) {
                $table->json('cash_denomination')->nullable();
            }
            if ($missing['total_sales']) {
                $table->decimal('total_sales', 22, 4)->default(0);
            }
            if ($missing['total_collected']) {
                $table->decimal('total_collected', 22, 4)->default(0);
            }
            if ($missing['variance']) {
                $table->decimal('variance', 22, 4)->default(0);
            }
            if ($missing['is_edited']) {
                $table->boolean('is_edited')->default(0);
            }
            if ($missing['updated_by']) {
                $table->unsignedInteger('updated_by')->nullable();
            }
            if ($missing['deleted_at']) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        // Compatibility repair only.  Never remove columns from a live tenant
        // in down(), because another SW build may already depend on them.
    }
};
