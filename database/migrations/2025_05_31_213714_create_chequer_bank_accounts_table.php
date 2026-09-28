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
        Schema::create('chequer_bank_accounts', function (Blueprint $table) {
            $table->integer('id', true);
            $table->unsignedInteger('business_id')->index('business_id');
            $table->integer('account_id')->index('account_id');
            $table->string('account_number', 30)->default('');
            $table->string('bank', 30)->default('');
            $table->string('branch', 30)->default('');
            $table->string('current_balance', 255)->default('0');
            $table->tinyInteger('is_visible')->default(1)->comment('0:Inactive, 1:Active, 2:Delete');
            $table->integer('created_by')->default(0);
            $table->integer('user_id')->default(0)->index('user_id');
            $table->timestamp('created')->useCurrent();
            $table->timestamp('transaction_date')->nullable();
            $table->integer('cheque_templete_id')->index('cheque_templete_id');
            $table->date('cheque_temp_regdate');
            $table->boolean('account_type')->default(true)->comment('1:Bank, 0:Loan');
            $table->boolean('cashier_account')->default(false)->comment('1:Cashier Account');
            $table->integer('updated_by');
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
        Schema::dropIfExists('chequer_bank_accounts');
    }
};
