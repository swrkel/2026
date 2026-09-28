<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUpdatedByToDistributionFreeIssuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('distribution_free_issues', function (Blueprint $table) {
            // Check if column doesn't exist before adding
            if (!Schema::hasColumn('distribution_free_issues', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
                $table->index('updated_by');
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
        Schema::table('distribution_free_issues', function (Blueprint $table) {
            if (Schema::hasColumn('distribution_free_issues', 'updated_by')) {
                $table->dropColumn('updated_by');
            }
        });
    }
}