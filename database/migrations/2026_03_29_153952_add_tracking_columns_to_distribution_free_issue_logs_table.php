<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTrackingColumnsToDistributionFreeIssueLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('distribution_free_issue_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('distribution_free_issue_logs', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('action');
            }
            
            if (!Schema::hasColumn('distribution_free_issue_logs', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address');
            }
            
            if (!Schema::hasColumn('distribution_free_issue_logs', 'old_data')) {
                $table->text('old_data')->nullable()->after('user_agent');
            }
            
            if (!Schema::hasColumn('distribution_free_issue_logs', 'new_data')) {
                $table->text('new_data')->nullable()->after('old_data');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('distribution_free_issue_logs', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'user_agent', 'old_data', 'new_data']);
        });
    }
}