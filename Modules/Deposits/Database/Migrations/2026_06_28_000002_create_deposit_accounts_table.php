<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDepositAccountsTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('deposit_accounts')) {
            Schema::create('deposit_accounts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('deposit_product_id')->nullable()->index();
                $table->unsignedBigInteger('banking_customer_id')->nullable()->index();
                $table->string('account_no')->unique();
                $table->string('customer_name')->nullable();
                $table->decimal('principal_amount', 20, 4)->default(0);
                $table->decimal('interest_rate', 20, 6)->default(0);
                $table->date('opened_on')->nullable();
                $table->date('maturity_on')->nullable();
                $table->decimal('current_balance', 20, 4)->default(0);
                $table->decimal('interest_accrued', 20, 4)->default(0);
                $table->string('status')->default('active')->index();
                $table->text('notes')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->unsignedInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('deposit_accounts');
    }
}
