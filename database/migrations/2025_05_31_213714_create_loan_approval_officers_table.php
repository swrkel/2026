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
        Schema::create('loan_approval_officers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('product_id')->index('product_id');
            $table->bigInteger('loan_id')->index('loan_id');
            $table->integer('user_id')->index('user_id');
            $table->enum('status', ['pending', 'approved', 'rejected', 'withdrawn'])->default('pending');

            $table->unique(['product_id', 'loan_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('loan_approval_officers');
    }
};
