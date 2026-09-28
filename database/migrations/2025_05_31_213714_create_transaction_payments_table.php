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
        Schema::create('transaction_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('transaction_id')->nullable()->index('transaction_id');
            $table->integer('business_id')->nullable()->index('business_id');
            $table->boolean('is_return')->default(false)->comment('Used during sales to return the change');
            $table->decimal('amount', 22, 6)->default(0);
            $table->string('method', 200)->nullable();
            $table->string('transaction_no')->nullable();
            $table->string('card_transaction_number')->nullable();
            $table->string('card_number')->nullable();
            $table->string('card_type')->nullable();
            $table->string('card_holder_name')->nullable();
            $table->string('card_month')->nullable();
            $table->string('card_year')->nullable();
            $table->string('card_security', 5)->nullable();
            $table->string('cheque_number')->nullable();
            $table->date('cheque_date')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->date('transfer_date')->nullable();
            $table->dateTime('paid_on')->nullable();
            $table->integer('created_by')->index();
            $table->integer('payment_for')->nullable();
            $table->integer('parent_id')->nullable()->index('parent_id');
            $table->string('note')->nullable();
            $table->string('document')->nullable();
            $table->unsignedInteger('payment_option_id')->nullable()->index('payment_option_id')->comment('property payment option id reference');
            $table->boolean('is_deposited')->default(false);
            $table->integer('is_realized')->default(0);
            $table->string('payment_ref_no')->nullable();
            $table->integer('account_id')->nullable()->index('account_id');
            $table->integer('related_account_id')->nullable()->default(0)->index('related_account_id');
            $table->unsignedInteger('double_entry_account')->nullable()->comment('use for excess payment ');
            $table->unsignedInteger('receivable_account')->nullable()->comment('use for short payment ');
            $table->enum('paid_in_type', ['customer_page', 'all_sale_page', 'settlement', 'customer_bulk', 'customer_simple'])->nullable();
            $table->softDeletes();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->string('reference_no', 50)->nullable();
            $table->string('payment_type', 50)->nullable();
            $table->boolean('post_dated_cheque')->nullable()->default(false);
            $table->integer('update_post_dated_cheque')->nullable()->default(0);
            $table->boolean('is_advance')->default(false);
            $table->integer('linked_customer_statement')->nullable();
            $table->integer('linked_vat_customer_statement')->nullable();
            $table->string('previous_prefix_no', 200)->nullable();
            $table->unsignedInteger('hms_transaction_class_id')->nullable();
            $table->string('shift_number', 200)->nullable();

            $table->index(['parent_id']);
            $table->index(['transaction_id'], 'transaction_payments_transaction_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_payments');
    }
};
