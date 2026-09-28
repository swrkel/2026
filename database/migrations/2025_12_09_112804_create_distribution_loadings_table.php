<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDistributionLoadingsTable extends Migration
{
    public function up()
    {
        Schema::create('distribution_loadings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->string('loading_no')->index();
            $table->dateTime('date_time')->nullable();
            $table->unsignedBigInteger('sales_rep_id')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('product_category_id')->nullable();
            $table->unsignedBigInteger('product_sub_category_id')->nullable();
            $table->decimal('total_sale_price', 22, 4)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('status')->default('draft'); // draft / confirmed
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('distribution_loadings');
    }
}
