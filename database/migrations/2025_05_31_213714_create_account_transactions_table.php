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
        if (Schema::hasTable('account_transactions')) {
            return;
        }

        Schema::create('account_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id')->index('account_id');
            $table->integer('related_account_id')->nullable()->default(0)->index('related_account_id');
            $table->integer('credit_related_account')->nullable();
            $table->integer('business_id')->nullable()->index('business_id');
            $table->enum('type', ['debit', 'credit']);
            $table->string('txnType', 255)->nullable();
            $table->enum('sub_type', ['opening_balance', 'fund_transfer', 'deposit', 'ledger_show', 'cheque_return_charges', 'purchase_edit', 'fleet_opening_balance', 'vat_payment', 'cheque_realize'])->nullable();
            $table->decimal('amount', 22, 6);
            $table->string('reff_no')->nullable();
            $table->dateTime('operation_date');
            $table->integer('created_by')->index();
            $table->integer('transaction_id')->nullable()->index();
            $table->integer('transaction_payment_id')->nullable()->index();
            $table->integer('transfer_transaction_id')->nullable()->index();
            $table->unsignedInteger('transaction_sell_line_id')->nullable()->index('transaction_sell_line_id')->comment('used for property sell, to fetch block and property');
            $table->integer('employee_advance_id')->nullable();
            $table->integer('sell_line_id')->nullable()->index('sell_line_id');
            $table->integer('purchase_line_id')->nullable()->index('purchase_line_id');
            $table->string('income_type')->nullable();
            $table->text('note')->nullable();
            $table->string('slip_no', 100)->nullable();
            $table->text('attachment')->nullable();
            $table->string('cheque_number')->nullable()->comment('only for transfer and deposit');
            $table->integer('journal_entry')->nullable();
            $table->boolean('journal_deleted')->default(false);
            $table->unsignedInteger('installment_id')->nullable()->index('installment_id');
            $table->unsignedInteger('payment_option_id')->nullable()->index('payment_option_id');
            $table->enum('updated_type', ['expense'])->nullable();
            $table->integer('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->boolean('reconcile_status')->default(false);
            $table->decimal('interest', 10, 0)->nullable();
            $table->string('bank_name', 50)->nullable();
            $table->string('cheque_numbers', 50)->nullable();
            $table->string('cheque_date', 50)->nullable();
            $table->text('payment_method')->nullable();
            $table->integer('fixed_asset_id')->nullable()->index('fixed_asset_id');
            $table->tinyInteger('post_dated_cheque')->default(0);
            $table->integer('update_post_dated_cheque')->nullable()->default(0);
            $table->integer('pair_at_id')->nullable()->index('pair_at_id');
            $table->integer('postdated_transafer_status')->default(0);
            $table->timestamp('new_deleted_at')->nullable();
            $table->integer('new_deleted_by')->nullable();
            $table->string('auto_transfer', 100)->nullable();
            $table->string('cheque_ref_no')->nullable();

            $table->index(['account_id']);
            $table->index(['transaction_id'], 'transaction_id');
            $table->index(['transaction_payment_id'], 'transaction_payment_id');
            $table->index(['transfer_transaction_id'], 'transfer_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('account_transactions');
    }
};
