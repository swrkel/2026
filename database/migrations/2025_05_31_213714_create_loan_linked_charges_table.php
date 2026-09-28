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
        Schema::create('loan_linked_charges', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('loan_id')->index('loan_id');
            $table->unsignedBigInteger('loan_charge_id')->index('loan_charge_id');
            $table->unsignedBigInteger('loan_charge_type_id')->nullable()->index('loan_charge_type_id');
            $table->unsignedBigInteger('loan_charge_option_id')->nullable()->index('loan_charge_option_id');
            $table->unsignedBigInteger('loan_transaction_id')->nullable()->index('loan_transaction_id');
            $table->text('name')->nullable();
            $table->decimal('amount', 65, 6);
            $table->decimal('calculated_amount', 65, 6)->nullable();
            $table->decimal('amount_paid_derived', 65, 6)->nullable();
            $table->decimal('amount_waived_derived', 65, 6)->nullable();
            $table->decimal('amount_written_off_derived', 65, 6)->nullable();
            $table->decimal('amount_outstanding_derived', 65, 6)->nullable();
            $table->tinyInteger('is_penalty')->default(0);
            $table->tinyInteger('waived')->default(0);
            $table->tinyInteger('is_paid')->default(0);
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
        Schema::dropIfExists('loan_linked_charges');
    }
};
