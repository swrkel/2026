<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanRecoveryAssignmentsTable
    extends Migration
{
    /**
     * Run Migration
     */

    public function up()
    {
        Schema::create(
            'loan_recovery_assignments',
            function (Blueprint $table) {

                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Loan Relationship
                |--------------------------------------------------------------------------
                */

                $table->unsignedBigInteger(
                    'loan_application_id'
                );

                /*
                |--------------------------------------------------------------------------
                | Recovery Officer
                |--------------------------------------------------------------------------
                */

                $table->unsignedBigInteger(
                    'recovery_officer_id'
                );

                /*
                |--------------------------------------------------------------------------
                | Assignment Status
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'status'
                )->default('active');

                /*
                |--------------------------------------------------------------------------
                | Assignment Timestamps
                |--------------------------------------------------------------------------
                */

                $table->timestamp(
                    'assigned_at'
                )->nullable();

                $table->timestamp(
                    'closed_at'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Escalation Notes
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
            }
        );
    }

    /**
     * Reverse Migration
     */

    public function down()
    {
        Schema::dropIfExists(
            'loan_recovery_assignments'
        );
    }
}
