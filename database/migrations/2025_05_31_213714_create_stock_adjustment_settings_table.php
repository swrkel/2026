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
        Schema::create('stock_adjustment_settings', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id')->index('business_id');
            $table->timestamp('date')->useCurrentOnUpdate()->useCurrent();
            $table->string('adjustment_type', 30);
            $table->integer('category_id')->index('category_id');
            $table->integer('sub_category_id')->index('sub_category_id');
            $table->integer('account_to_link');
            $table->integer('stock_group');
            $table->integer('stock_account');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->default('0000-00-00 00:00:00');
            $table->integer('created_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stock_adjustment_settings');
    }
};
