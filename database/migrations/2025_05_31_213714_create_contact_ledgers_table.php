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
        Schema::create('contact_ledgers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('contact_id')->nullable()->index('contact_id');
            $table->enum('type', ['debit', 'credit']);
            $table->enum('sub_type', ['cheque_return_charges', ' payment'])->nullable();
            $table->decimal('amount', 22, 4);
            $table->string('reff_no')->nullable();
            $table->dateTime('operation_date');
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('transaction_id')->nullable()->index('transaction_id');
            $table->unsignedInteger('transaction_payment_id')->nullable()->index('transaction_payment_id');
            $table->integer('transaction_sell_line_id')->nullable()->index('transaction_sell_line_id')->comment('used for property sell, to fetch block and property');
            $table->integer('income_type')->nullable();
            $table->unsignedInteger('installment_id')->nullable()->index('installment_id');
            $table->unsignedInteger('payment_option_id')->nullable()->index('payment_option_id');
            $table->text('note')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

            $table->index(['transaction_id', 'contact_id'], 'contact_ledgers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contact_ledgers');
    }
};
