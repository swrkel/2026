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
        Schema::create('printed_cheque_details', function (Blueprint $table) {
            $table->integer('id', true);
            $table->unsignedInteger('business_id')->index('business_id');
            $table->integer('template_id')->default(0)->index('template_id');
            $table->integer('user_id')->default(0)->index('user_id');
            $table->string('payee', 255)->nullable();
            $table->string('bank_account_no', 255)->nullable();
            $table->string('cheque_no', 255)->default('');
            $table->float('cheque_amount', 15);
            $table->date('cheque_date');
            $table->string('voucher_id', 255)->nullable()->index('voucher_id');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
            $table->string('payee_tempname', 255)->default('');
            $table->text('stampvalu');
            $table->text('amount_word');
            $table->text('refrence')->nullable();
            $table->string('status', 50)->nullable();
            $table->unsignedBigInteger('purchase_order_id')->nullable()->index('purchase_order_id');
            $table->decimal('supplier_paid_amount', 10)->nullable();
            $table->string('print_type', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('printed_cheque_details');
    }
};
