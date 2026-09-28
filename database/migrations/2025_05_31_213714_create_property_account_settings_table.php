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
        Schema::create('property_account_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('property_id')->index('property_id');
            $table->date('date');
            $table->integer('income_account_id')->index('income_account_id');
            $table->integer('expense_account_id')->index('expense_account_id');
            $table->integer('interest_income_account_id')->index('interest_income_account_id');
            $table->integer('penalty_income_account_id')->index('penalty_income_account_id');
            $table->integer('account_receivable_account_id')->index('account_receivable_account_id');
            $table->integer('capital_income_account_id')->index('capital_income_account_id');
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
        Schema::dropIfExists('property_account_settings');
    }
};
