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
        Schema::create('loan_product_linked_charges', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('loan_product_id')->index('loan_product_id');
            $table->unsignedBigInteger('loan_charge_id')->index('loan_charge_id');
            $table->boolean('is_overdue')->nullable()->default(false);
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
        Schema::dropIfExists('loan_product_linked_charges');
    }
};
