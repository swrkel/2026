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
        Schema::create('work_order_items', function (Blueprint $table) {
            $table->integer('id', true);
            $table->unsignedInteger('work_order_id')->index('work_order_id');
            $table->integer('item_id')->index('item_id');
            $table->integer('qty');
            $table->integer('required_qty');
            $table->decimal('weight_showroom_product', 15, 4);
            $table->decimal('required_unit_weight', 15, 4);
            $table->decimal('gold_qty', 15, 4);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('work_order_items');
    }
};
