<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFreeIssuesJsonToDistributionDailySummaryLines extends Migration
{
    public function up()
    {
        Schema::table('distribution_daily_summary_lines', function (Blueprint $table) {
            $table->json('free_issues_json')->nullable()->after('products_json');
        });
    }

    public function down()
    {
        Schema::table('distribution_daily_summary_lines', function (Blueprint $table) {
            $table->dropColumn('free_issues_json');
        });
    }
}