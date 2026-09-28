<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * IS1991 (#2): somewhere to put the two selections the category checkboxes ask for.
 *
 * 2026_08_06_000002 added the three flags themselves - vat_input_claimed,
 * is_sub_category and is_employee - but a flag only records that a choice is
 * REQUIRED. Ticking "Sub Category" is supposed to reveal a Parent Category
 * dropdown, and ticking "Employee" an Employees dropdown, and neither had a
 * column to save into:
 *
 *     expnew_categories.parent_id      did not exist
 *     expnew_categories.employee_id    did not exist
 *
 * so the dropdowns had nothing to hold. Both nullable: a category with neither
 * flag set stores nothing, which is every category on the system today.
 *
 * employee_id is intentionally NOT a foreign key. Employees belong to the HR
 * module and may live in a different table depending on which HR module is
 * installed - see Services/EmployeeOptionService. A constraint here would make
 * this module fail to migrate on an installation without that module.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('expnew_categories')) {
            return;
        }

        Schema::table('expnew_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('expnew_categories', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('code');
            }

            if (! Schema::hasColumn('expnew_categories', 'employee_id')) {
                $table->unsignedBigInteger('employee_id')->nullable()->after('parent_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('expnew_categories')) {
            return;
        }

        Schema::table('expnew_categories', function (Blueprint $table) {
            foreach (['employee_id', 'parent_id'] as $column) {
                if (Schema::hasColumn('expnew_categories', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
