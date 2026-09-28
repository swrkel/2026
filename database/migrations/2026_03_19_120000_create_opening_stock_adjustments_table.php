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
        Schema::create('opening_stock_adjustments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('product_id')->index('product_id');
            $table->unsignedInteger('variation_id')->index('variation_id');
            $table->unsignedInteger('category_id')->nullable()->index('category_id');
            $table->unsignedInteger('sub_category_id')->nullable()->index('sub_category_id');
            $table->unsignedInteger('location_id')->index('location_id');
            $table->decimal('original_quantity', 22, 4)->default(0);
            $table->decimal('adjusted_quantity', 22, 4)->default(0);
            $table->decimal('quantity_difference', 22, 4)->default(0);
            $table->text('note')->nullable();
            $table->dateTime('transaction_date')->nullable();
            $table->unsignedInteger('created_by')->index();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('variation_id')->references('id')->on('variations')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('sub_category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('location_id')->references('id')->on('business_locations')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('opening_stock_adjustments');
    }
};
