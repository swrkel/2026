<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MA-002 (Issue 3): Add Expense form needs two new selections,
 * "Applicable Tax" (None / VAT) and "VAT Invoice" (Yes / No).
 *
 * Written in the same defensive style as the existing
 * add_accounting_module_to_expnew_expenses_table migration so it is
 * safe to re-run across every tenant database.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('expnew_expenses')) {
            return;
        }

        Schema::table('expnew_expenses', function (Blueprint $table): void {
            if (! Schema::hasColumn('expnew_expenses', 'applicable_tax')) {
                // 'none' | 'vat'. Defaulted to 'none' so existing rows keep
                // their current (untaxed) meaning.
                $table->string('applicable_tax', 20)
                    ->nullable()
                    ->default('none');
            }

            if (! Schema::hasColumn('expnew_expenses', 'vat_invoice')) {
                // 0 = No, 1 = Yes. Existing rows default to No.
                $table->boolean('vat_invoice')
                    ->nullable()
                    ->default(0);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('expnew_expenses')) {
            return;
        }

        Schema::table('expnew_expenses', function (Blueprint $table): void {
            if (Schema::hasColumn('expnew_expenses', 'applicable_tax')) {
                $table->dropColumn('applicable_tax');
            }

            if (Schema::hasColumn('expnew_expenses', 'vat_invoice')) {
                $table->dropColumn('vat_invoice');
            }
        });
    }
};
