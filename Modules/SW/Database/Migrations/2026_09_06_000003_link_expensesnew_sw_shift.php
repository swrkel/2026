<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * IS2201 - persist the optional SW shift selected on Expenses New payments.
 *
 * Expenses New is standalone, so the shift link belongs to its payment row.
 * This migration only adds the nullable compatibility column when that module
 * exists in the same tenant database. It never changes existing amounts.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('expnew_expense_payments')
            || Schema::hasColumn('expnew_expense_payments', 'sw_shift_no')) {
            return;
        }

        Schema::table('expnew_expense_payments', function (Blueprint $table) {
            $table->string('sw_shift_no', 60)
                ->nullable()
                ->index('expnew_payments_sw_shift_idx');
        });
    }

    public function down(): void
    {
        // Non-destructive: production expense/shift history must not be dropped.
    }
};
