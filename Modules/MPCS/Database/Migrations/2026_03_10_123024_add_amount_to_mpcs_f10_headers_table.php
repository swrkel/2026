<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAmountToMpcsF10HeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mpcs_f10_headers', function (Blueprint $table) {
            $table->decimal('total_amount', 15, 4)->default(0)->after('form_no');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mpcs_f10_headers', function (Blueprint $table) {
            $table->dropColumn('total_amount');
        });
    }
}
