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
        Schema::create('variation_transfers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->date('date');
            $table->unsignedInteger('from_location');
            $table->unsignedInteger('from_store');
            $table->unsignedInteger('to_location');
            $table->unsignedInteger('to_store');
            $table->unsignedInteger('category_id')->index('category_id');
            $table->unsignedInteger('sub_category_id')->nullable()->index('sub_category_id');
            $table->unsignedInteger('from_variation_id')->index('from_variation_id');
            $table->unsignedInteger('to_variation_id')->index('to_variation_id');
            $table->decimal('qty', 15, 6);
            $table->decimal('unit_cost', 15, 6);
            $table->decimal('total_cost', 15, 6);
            $table->unsignedInteger('sell_transfer_id')->nullable()->index('sell_transfer_id');
            $table->unsignedInteger('purchase_transfer_id')->nullable()->index('purchase_transfer_id');
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
        Schema::dropIfExists('variation_transfers');
    }
};
