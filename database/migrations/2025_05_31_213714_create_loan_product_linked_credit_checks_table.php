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
        Schema::create('loan_product_linked_credit_checks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('loan_product_id')->index('loan_product_id');
            $table->unsignedBigInteger('loan_credit_check_id')->index('loan_credit_check_id');
            $table->integer('check_order')->nullable();
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
        Schema::dropIfExists('loan_product_linked_credit_checks');
    }
};
