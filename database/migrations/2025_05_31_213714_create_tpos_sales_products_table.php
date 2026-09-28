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
        Schema::create('tpos_sales_products', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('sale_id')->index('sale_id');
            $table->integer('product_id')->index('product_id');
            $table->integer('category_id')->index('category_id');
            $table->string('unit')->nullable();
            $table->decimal('price', 22, 5);
            $table->decimal('updated_price', 22, 5)->nullable();
            $table->decimal('qty', 22, 5);
            $table->decimal('updated_qty', 10)->nullable();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->timestamp('created_at')->default('0000-00-00 00:00:00');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tpos_sales_products');
    }
};
