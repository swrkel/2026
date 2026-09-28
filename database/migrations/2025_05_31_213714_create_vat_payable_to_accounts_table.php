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
        Schema::create('vat_payable_to_accounts', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id');
            $table->integer('account_id');
            $table->string('type', 50)->default('vat_payable_account');
            $table->decimal('amount', 15, 3)->nullable();
            $table->integer('transaction_id')->nullable();
            $table->text('note')->nullable();
            $table->integer('created_by');
            $table->timestamp('created_at')->useCurrentOnUpdate()->useCurrent();
            $table->timestamp('updated_at')->default('0000-00-00 00:00:00');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vat_payable_to_accounts');
    }
};
