<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('distribution_free_issues', function (Blueprint $table) {
            if (!Schema::hasColumn('distribution_free_issues', 'form_no')) {
                $table->integer('form_no')->nullable()->after('business_id');
            }
            if (!Schema::hasColumn('distribution_free_issues', 'date_since')) {
                $table->dateTime('date_since')->nullable()->after('form_no');
            }
            if (!Schema::hasColumn('distribution_free_issues', 'date_till')) {
                $table->dateTime('date_till')->nullable()->after('date_since');
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
            $table->dropColumn(['form_no', 'date_since', 'date_till']);
        });
    }
};
