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
        Schema::create('stock_transfer_requests', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('request_location');
            $table->unsignedInteger('request_to_location');
            $table->unsignedInteger('category_id')->index('category_id');
            $table->unsignedInteger('sub_category_id')->index('sub_category_id');
            $table->unsignedInteger('product_id')->index('product_id');
            $table->decimal('qty', 15)->default(0);
            $table->date('delivery_need_on');
            $table->enum('status', ['requested', 'issued', 'transit', 'received'])->default('requested');
            $table->enum('notification', ['ok', 'stop'])->default('ok');
            $table->integer('good_condition')->default(0);
            $table->integer('damage')->default(0);
            $table->integer('short')->default(0);
            $table->integer('expire')->default(0);
            $table->unsignedInteger('transaction_id')->nullable()->index('transaction_id');
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
        Schema::dropIfExists('stock_transfer_requests');
    }
};
