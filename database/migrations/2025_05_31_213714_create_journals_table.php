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
        Schema::create('journals', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('journal_id')->index('journal_id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('location_id')->index('location_id');
            $table->unsignedInteger('account_type_id')->index('account_type_id');
            $table->integer('account_id')->index('account_id');
            $table->date('date');
            $table->decimal('debit_amount', 15)->nullable();
            $table->unsignedDecimal('credit_amount', 15)->nullable();
            $table->text('note')->nullable();
            $table->enum('is_opening_balance', ['yes', 'no'])->nullable();
            $table->unsignedInteger('added_by');
              // 🔹 New columns added:
            $table->unsignedInteger('pump_operator')->nullable()->index('pump_operator'); // Pump operator reference
            $table->enum('show_in_ledger', ['yes', 'no', 'customer', 'supplier'])->default('yes'); // Visibility in ledger
            $table->unsignedInteger('customer_show_in')->nullable()->index('customer_show_in'); // Link to customer if needed
            $table->unsignedInteger('supplier_show_in')->nullable()->index('supplier_show_in'); // Link to supplier if needed
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('journals');
    }
};
