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
        Schema::create('loan_collateral_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('loan_collateral_id')->index('loan_collateral_id');
            $table->integer('updated_by_user_id')->index('updated_by_user_id');
            $table->string('status');
            $table->date('status_change_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('loan_collateral_history');
    }
};
