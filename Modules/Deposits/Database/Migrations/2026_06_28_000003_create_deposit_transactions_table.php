<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDepositTransactionsTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('deposit_transactions')) {
            Schema::create('deposit_transactions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('deposit_account_id')->index();
                $table->string('transaction_no')->nullable()->index();
                $table->string('type')->default('deposit')->index();
                $table->date('transaction_date')->nullable();
                $table->decimal('amount', 20, 4)->default(0);
                $table->decimal('balance_after', 20, 4)->default(0);
                $table->string('payment_method')->nullable();
                $table->string('reference_no')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('deposit_transactions');
    }
}
