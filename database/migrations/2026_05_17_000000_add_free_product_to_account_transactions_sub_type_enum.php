<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE account_transactions MODIFY COLUMN sub_type ENUM('opening_balance', 'fund_transfer', 'deposit', 'ledger_show', 'cheque_return_charges', 'purchase_edit', 'fleet_opening_balance', 'vat_payment', 'cheque_realize', 'free_product') DEFAULT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE account_transactions MODIFY COLUMN sub_type ENUM('opening_balance', 'fund_transfer', 'deposit', 'ledger_show', 'cheque_return_charges', 'purchase_edit', 'fleet_opening_balance', 'vat_payment', 'cheque_realize') DEFAULT NULL");
    }
};
