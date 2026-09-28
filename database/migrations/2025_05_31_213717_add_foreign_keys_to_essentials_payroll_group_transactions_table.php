<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('essentials_payroll_group_transactions', function (Blueprint $table) {
            $table->foreign(['payroll_group_id'])->references(['id'])->on('essentials_payroll_groups')->onUpdate('NO ACTION')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('essentials_payroll_group_transactions', function (Blueprint $table) {
            $table->dropForeign('essentials_payroll_group_transactions_payroll_group_id_foreign');
        });
    }
};
