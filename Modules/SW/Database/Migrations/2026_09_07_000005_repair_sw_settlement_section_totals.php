<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repair section-total columns on older SW settlement headers.
 *
 * Some tenant databases already had sw_settlements before the current SW
 * settlement header was introduced. Because the original CREATE migration was
 * already recorded as executed, those tables can be missing one or more of the
 * four section totals even after later compatibility repairs have run.
 *
 * This migration is intentionally idempotent and never rewrites existing rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sw_settlements')) {
            return;
        }

        $missing = [
            'total_meter_sales' => ! Schema::hasColumn('sw_settlements', 'total_meter_sales'),
            'total_other_sales' => ! Schema::hasColumn('sw_settlements', 'total_other_sales'),
            'total_other_income' => ! Schema::hasColumn('sw_settlements', 'total_other_income'),
            'total_credit_sales' => ! Schema::hasColumn('sw_settlements', 'total_credit_sales'),
        ];

        if (! in_array(true, $missing, true)) {
            return;
        }

        Schema::table('sw_settlements', function (Blueprint $table) use ($missing) {
            if ($missing['total_meter_sales']) {
                $table->decimal('total_meter_sales', 22, 4)->default(0);
            }
            if ($missing['total_other_sales']) {
                $table->decimal('total_other_sales', 22, 4)->default(0);
            }
            if ($missing['total_other_income']) {
                $table->decimal('total_other_income', 22, 4)->default(0);
            }
            if ($missing['total_credit_sales']) {
                $table->decimal('total_credit_sales', 22, 4)->default(0);
            }
        });
    }

    public function down(): void
    {
        // Compatibility repair only. Never remove live settlement columns.
    }
};
