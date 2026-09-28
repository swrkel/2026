<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBalanceColumnsToLoansTable extends Migration
{
    public function up()
    {
        Schema::table('loans', function (Blueprint $table) {

            $table->decimal('principal_paid', 22, 2)
                ->default(0)
                ->after('principal_amount');

            $table->decimal('interest_paid', 22, 2)
                ->default(0)
                ->after('principal_paid');

            $table->decimal('penalty_paid', 22, 2)
                ->default(0)
                ->after('interest_paid');

            $table->decimal('principal_outstanding', 22, 2)
                ->default(0)
                ->after('penalty_paid');

            $table->decimal('interest_outstanding', 22, 2)
                ->default(0)
                ->after('principal_outstanding');

            $table->decimal('penalty_outstanding', 22, 2)
                ->default(0)
                ->after('interest_outstanding');

        });
    }

    public function down()
    {
        Schema::table('loans', function (Blueprint $table) {

            $table->dropColumn([
                'principal_paid',
                'interest_paid',
                'penalty_paid',
                'principal_outstanding',
                'interest_outstanding',
                'penalty_outstanding'
            ]);

        });
    }
}