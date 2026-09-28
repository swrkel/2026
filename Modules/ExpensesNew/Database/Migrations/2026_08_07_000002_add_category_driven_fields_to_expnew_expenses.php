<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MA-002 (S-621 #2): storage for the three category-driven expense fields.
 *
 * The three checkboxes on a category - VAT Input Claimed, Sub Category and
 * Employee - were saved, but nothing on the Add Expenses page could act on
 * them because there was nowhere to record what the user picked:
 *
 *     expnew_expenses.vat_category_id     did not exist
 *     expnew_expenses.sub_category_id     did not exist
 *     expnew_expenses.employee_id         did not exist
 *
 * So the feature was unbuilt rather than broken. These three columns are what
 * make the dropdowns possible.
 *
 * All nullable: a category with none of the flags set records nothing, which
 * is every existing expense on the system.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('expnew_expenses')) {
            return;
        }

        Schema::table('expnew_expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('expnew_expenses', 'vat_category_id')) {
                $table->unsignedInteger('vat_category_id')
                    ->nullable()
                    ->after('category_id');
            }

            if (! Schema::hasColumn('expnew_expenses', 'sub_category_id')) {
                $table->unsignedInteger('sub_category_id')
                    ->nullable()
                    ->after('vat_category_id');
            }

            if (! Schema::hasColumn('expnew_expenses', 'employee_id')) {
                $table->unsignedInteger('employee_id')
                    ->nullable()
                    ->after('sub_category_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('expnew_expenses')) {
            return;
        }

        Schema::table('expnew_expenses', function (Blueprint $table) {
            foreach (['employee_id', 'sub_category_id', 'vat_category_id'] as $column) {
                if (Schema::hasColumn('expnew_expenses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
