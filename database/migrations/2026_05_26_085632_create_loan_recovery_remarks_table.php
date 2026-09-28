<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanRecoveryRemarksTable extends Migration
{
    public function up()
    {
        Schema::create('loan_recovery_remarks', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('loan_application_id');

            $table->text('remark')->nullable();

            $table->date('next_followup_date')->nullable();

            $table->boolean('promise_to_pay')
                  ->default(false);

            $table->decimal('promised_amount', 20, 2)
                  ->nullable();

            $table->date('promised_payment_date')
                  ->nullable();

            $table->unsignedBigInteger('created_by')
                  ->nullable();

            $table->timestamps();

        });
    }

    public function down()
    {
        Schema::dropIfExists(
            'loan_recovery_remarks'
        );
    }
}