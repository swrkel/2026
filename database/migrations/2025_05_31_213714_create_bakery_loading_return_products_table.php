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
        Schema::create('bakery_loading_return_products', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('return_id')->index('return_id');
            $table->integer('product_id')->index('product_id');
            $table->integer('loading_id')->index('loading_id');
            $table->decimal('qty_returned', 10, 0);
            $table->decimal('amount_returned', 10, 0);
            $table->decimal('received_amount', 10, 0);
            $table->timestamp('created_at')->useCurrentOnUpdate()->useCurrent();
            $table->timestamp('updated_at')->default('0000-00-00 00:00:00');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bakery_loading_return_products');
    }
};
