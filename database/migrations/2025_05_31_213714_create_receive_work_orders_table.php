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
        Schema::create('receive_work_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('location_id')->index('location_id');
            $table->unsignedInteger('goldsmith_id')->index('goldsmith_id');
            $table->dateTime('date_and_time');
            $table->integer('receive_work_order_no');
            $table->unsignedInteger('receiving_store_id')->index('receiving_store_id');
            $table->unsignedInteger('work_order_id')->index('work_order_id');
            $table->unsignedInteger('item_id')->index('item_id');
            $table->integer('gold_grade')->nullable();
            $table->decimal('item_weight', 15)->nullable();
            $table->decimal('required_item_weight', 15);
            $table->integer('required_qty');
            $table->integer('received_qty');
            $table->unsignedInteger('category_id')->index('category_id');
            $table->decimal('received_weight_for_all_items', 15);
            $table->decimal('wastage_per_8g', 15, 4);
            $table->decimal('total_wastage', 15, 4);
            $table->decimal('total_stone_weight', 15, 4);
            $table->decimal('labour_cost', 15, 4);
            $table->text('item_details')->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('created_by');
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
        Schema::dropIfExists('receive_work_orders');
    }
};
