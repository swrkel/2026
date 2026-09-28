<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDistributionDiscountsTable extends Migration
{
    public function up()
    {
        Schema::create('distribution_discounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id');

            $table->dateTime('date_time');

            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('sub_category_id')->nullable();

            $table->unsignedBigInteger('unit_id')->nullable();
            $table->decimal('qty', 22, 4)->nullable();

            $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('max_discount', 22, 4)->default(0);

            $table->timestamps();

            $table->index(['business_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('distribution_discounts');
    }
}
