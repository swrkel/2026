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
        Schema::create('tank_purchase_lines', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('transaction_id')->index('transaction_id');
            $table->unsignedInteger('tank_id')->index('tank_id');
            $table->unsignedInteger('product_id')->index('product_id');
            $table->decimal('quantity', 15, 5);
            $table->decimal('instock_qty', 15, 6)->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->timestamp('new_deleted_at')->nullable();
            $table->integer('new_deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tank_purchase_lines');
    }
};
