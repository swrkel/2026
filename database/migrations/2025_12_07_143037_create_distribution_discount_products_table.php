<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDistributionDiscountProductsTable extends Migration
{
    public function up()
    {
        Schema::create('distribution_discount_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('discount_id');
            $table->unsignedBigInteger('product_id');

            $table->timestamps();

            $table->foreign('discount_id')
                ->references('id')->on('distribution_discounts')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('distribution_discount_products');
    }
}
