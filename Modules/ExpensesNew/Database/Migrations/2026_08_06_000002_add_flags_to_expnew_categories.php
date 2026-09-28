<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MA-002 (S-612): three flags on an expense category.
 *
 *     vat_input_claimed   the category's VAT can be reclaimed as input VAT
 *     is_sub_category     this category sits under another
 *     is_employee         the category represents an employee cost
 *
 * expnew_categories has 12 columns and none of these is among them, so the
 * checkboxes had nowhere to store their value.
 *
 * All three default to 0, which is what every existing category means today -
 * so adding them changes nothing about any category already on the system.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('expnew_categories')) {
            return;
        }

        Schema::table('expnew_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('expnew_categories', 'vat_input_claimed')) {
                $table->boolean('vat_input_claimed')
                    ->default(0)
                    ->after('is_active');
            }

            if (! Schema::hasColumn('expnew_categories', 'is_sub_category')) {
                $table->boolean('is_sub_category')
                    ->default(0)
                    ->after('vat_input_claimed');
            }

            if (! Schema::hasColumn('expnew_categories', 'is_employee')) {
                $table->boolean('is_employee')
                    ->default(0)
                    ->after('is_sub_category');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('expnew_categories')) {
            return;
        }

        Schema::table('expnew_categories', function (Blueprint $table) {
            foreach (['is_employee', 'is_sub_category', 'vat_input_claimed'] as $column) {
                if (Schema::hasColumn('expnew_categories', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
