<?php

// Modified by Engr. Alex -- task 7882: Issue 3 - add description_constant_details and created_by columns for Prefix & Numbers tab

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDescriptionAndCreatedByToLiocReportCustomizeds extends Migration
{
    public function up()
    {
        Schema::table('lioc_report_customizeds', function (Blueprint $table) {
            if (!Schema::hasColumn('lioc_report_customizeds', 'description_constant_details')) {
                $table->text('description_constant_details')->nullable()->after('constant_value');
            }
            if (!Schema::hasColumn('lioc_report_customizeds', 'created_by')) {
                $table->unsignedInteger('created_by')->nullable()->after('description_constant_details');
            }
        });
    }

    public function down()
    {
        Schema::table('lioc_report_customizeds', function (Blueprint $table) {
            $table->dropColumn(['description_constant_details', 'created_by']);
        });
    }
}
