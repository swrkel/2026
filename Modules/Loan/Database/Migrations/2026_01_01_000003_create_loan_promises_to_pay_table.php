<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanPromisesToPayTable
    extends Migration
{
    public function up()
    {
        Schema::create(
            'loan_promises_to_pay',
            function (Blueprint $table) {

                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Relationships
                |--------------------------------------------------------------------------
                */

                $table->unsignedBigInteger(
                    'loan_application_id'
                );

                $table->unsignedBigInteger(
                    'recovery_officer_id'
                );

                /*
                |--------------------------------------------------------------------------
                | Promise Details
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'promised_amount',
                    15,
                    2
                );

                $table->date(
                    'promised_date'
                );

                /*
                |--------------------------------------------------------------------------
                | Status
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'status'
                )->default('pending');

                /*
                |--------------------------------------------------------------------------
                | Fulfillment
                |--------------------------------------------------------------------------
                */

                $table->timestamp(
                    'fulfilled_at'
                )->nullable();

                $table->timestamp(
                    'broken_at'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Notes
                |--------------------------------------------------------------------------
                */

                $table->text(
                    'notes'
                )->nullable();

                $table->timestamps();

                /*
                |--------------------------------------------------------------------------
                | Indexes
                |--------------------------------------------------------------------------
                */

                $table->index(
                    'loan_application_id'
                );

                $table->index(
                    'recovery_officer_id'
                );

                $table->index(
                    'status'
                );

                $table->index(
                    'promised_date'
                );
            }
        );
    }

    public function down()
    {
        Schema::dropIfExists(
            'loan_promises_to_pay'
        );
    }
}