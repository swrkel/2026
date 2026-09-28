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
        Schema::create('user_store_permissions', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id');
            $table->integer('store_id');
            $table->integer('user_id');
            $table->integer('sell')->nullable();
            $table->integer('purchase')->nullable();
            $table->integer('stores_transfer')->nullable();
            $table->integer('stock_adjustment')->nullable();
            $table->integer('sell_return')->nullable();
            $table->integer('created_by');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->default('0000-00-00 00:00:00');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_store_permissions');
    }
};
