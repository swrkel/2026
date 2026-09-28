<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormF18DetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_f18_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('header_id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('from_location_id');
            $table->unsignedInteger('to_location_id');
            $table->decimal('qty', 20, 4)->default(0);

            // Snapshot prices (inclusive of taxes)
            $table->decimal('issued_purchase_unit_price', 20, 4)->default(0);
            $table->decimal('issued_purchase_total', 20, 4)->default(0);
            $table->decimal('issued_sale_unit_price', 20, 4)->default(0);
            $table->decimal('issued_sale_total', 20, 4)->default(0);

            $table->decimal('received_purchase_unit_price', 20, 4)->default(0);
            $table->decimal('received_purchase_total', 20, 4)->default(0);
            $table->decimal('received_sale_unit_price', 20, 4)->default(0);
            $table->decimal('received_sale_total', 20, 4)->default(0);

            $table->timestamps();

            $table->index(['business_id', 'header_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('form_f18_details');
    }
}

