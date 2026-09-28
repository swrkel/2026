<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanCollectionActionsTable
    extends Migration
{
    public function up()
    {
        Schema::create(
            'loan_collection_actions',
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
                | Collection Activity
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'action_type'
                );

                $table->text(
                    'notes'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Promise To Pay
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'promised_amount',
                    15,
                    2
                )->nullable();

                $table->date(
                    'promised_date'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Follow Up
                |--------------------------------------------------------------------------
                */

                $table->dateTime(
                    'next_follow_up_at'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Status
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'status'
                )->default('open');

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
                    'action_type'
                );

                $table->index(
                    'status'
                );
            }
        );
    }

    public function down()
    {
        Schema::dropIfExists(
            'loan_collection_actions'
        );
    }
}