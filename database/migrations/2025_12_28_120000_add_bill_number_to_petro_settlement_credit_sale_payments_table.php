<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBillNumberToPetroSettlementCreditSalePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('settlement_credit_sale_payments', function (Blueprint $table) {
            $table->string('bill_number')->nullable()->after('order_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settlement_credit_sale_payments', function (Blueprint $table) {
            $table->dropColumn('bill_number');
        });
    }
}
