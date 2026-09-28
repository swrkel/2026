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
        Schema::create('loan_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('loan_id')->index('loan_id');
            $table->unsignedBigInteger('created_by_id')->nullable()->index('created_by_id');
            $table->unsignedInteger('location_id')->nullable()->index('location_id');
            $table->unsignedBigInteger('payment_detail_id')->nullable()->index('payment_detail_id');
            $table->text('name')->nullable();
            $table->decimal('amount', 65, 6);
            $table->decimal('credit', 65, 6)->nullable();
            $table->decimal('debit', 65, 6)->nullable();
            $table->decimal('principal_repaid_derived', 65, 6)->default(0);
            $table->decimal('interest_repaid_derived', 65, 6)->default(0);
            $table->decimal('fees_repaid_derived', 65, 6)->default(0);
            $table->decimal('penalties_repaid_derived', 65, 6)->default(0);
            $table->unsignedBigInteger('loan_transaction_type_id')->index('loan_transaction_type_id');
            $table->tinyInteger('reversed')->default(0);
            $table->tinyInteger('reversible')->default(0);
            $table->date('submitted_on')->nullable();
            $table->date('due_date')->nullable();
            $table->date('created_on')->nullable();
            $table->enum('status', ['pending', 'approved', 'declined'])->nullable();
            $table->string('reference')->nullable();
            $table->string('gateway_id')->nullable()->index('gateway_id');
            $table->text('description')->nullable();
            $table->text('payment_gateway_data')->nullable();
            $table->tinyInteger('online_transaction')->default(0);
            $table->timestamps();

            $table->index(['loan_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('loan_transactions');
    }
};
