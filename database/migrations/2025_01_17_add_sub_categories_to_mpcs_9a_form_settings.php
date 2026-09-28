<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('mpcs_9a_form_settings')) {
            return;
        }

        Schema::table('mpcs_9a_form_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('mpcs_9a_form_settings', 'sub_categories_data')) {
                $table->longText('sub_categories_data')->nullable()->after('pre_day_grand_total')->comment('JSON data for sub-categories with their previous day values');
            }
            if (! Schema::hasColumn('mpcs_9a_form_settings', 'no_of_rows_per_page')) {
                $table->integer('no_of_rows_per_page')->nullable()->after('sub_categories_data')->comment('Number of rows to display per page');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('mpcs_9a_form_settings')) {
            return;
        }

        Schema::table('mpcs_9a_form_settings', function (Blueprint $table) {
            if (Schema::hasColumn('mpcs_9a_form_settings', 'sub_categories_data')) {
                $table->dropColumn('sub_categories_data');
            }
            if (Schema::hasColumn('mpcs_9a_form_settings', 'no_of_rows_per_page')) {
                $table->dropColumn('no_of_rows_per_page');
            }
        });
    }
};
