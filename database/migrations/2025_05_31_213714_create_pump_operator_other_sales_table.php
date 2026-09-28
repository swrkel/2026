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
        Schema::create('pump_operator_other_sales', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('other_sale_id')->nullable()->index('other_sale_id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('store_id')->index('store_id');
            $table->unsignedInteger('product_id')->index('product_id');
            $table->decimal('price', 15, 5);
            $table->decimal('qty', 15, 5);
            $table->decimal('balance_stock', 15, 5);
            $table->decimal('discount', 15)->nullable();
            $table->enum('discount_type', ['fixed', 'percentage'])->nullable();
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->decimal('sub_total', 15, 5);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->unsignedInteger('shift_id')->nullable()->index('shift_id');
            $table->integer('transaction_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pump_operator_other_sales');
    }
};
