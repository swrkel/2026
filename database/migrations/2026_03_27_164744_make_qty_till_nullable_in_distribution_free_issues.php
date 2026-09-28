<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeQtyTillNullableInDistributionFreeIssues extends Migration
{
    public function up()
    {
        Schema::table('distribution_free_issues', function (Blueprint $table) {
            $table->integer('qty_till')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('distribution_free_issues', function (Blueprint $table) {
            $table->integer('qty_till')->nullable(false)->change();
        });
    }
}