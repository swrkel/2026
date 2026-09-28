<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQtyTypeToDistributionFreeIssuesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('distribution_free_issues', 'qty_type')) {
            Schema::table('distribution_free_issues', function (Blueprint $table) {
                $table->enum('qty_type', ['single', 'range'])->default('range')->after('free_qty');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('distribution_free_issues', 'qty_type')) {
            Schema::table('distribution_free_issues', function (Blueprint $table) {
                $table->dropColumn('qty_type');
            });
        }
    }
}