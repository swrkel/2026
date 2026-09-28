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
        Schema::create('settlement_expense_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('settlement_no');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->string('expense_number');
            $table->integer('category_id')->index('category_id');
            $table->string('reference_no');
            $table->unsignedInteger('account_id')->index('account_id');
            $table->text('reason')->nullable();
            $table->decimal('amount', 15, 6);
            $table->unsignedInteger('transaction_id')->nullable()->index('transaction_id')->comment('reference transaction id');
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
        Schema::dropIfExists('settlement_expense_payments');
    }
};
