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
        Schema::create('price_changes_headers', function (Blueprint $table) {
            $table->integer('id', true);
            $table->unsignedInteger('business_id')->index('business_id');
            $table->string('date')->nullable();
            $table->unsignedInteger('form_no');
            $table->unsignedInteger('location_id')->nullable()->index('location_id');
            $table->unsignedInteger('store_id')->nullable()->index('store_id');
            $table->unsignedInteger('category_id')->nullable()->index('category_id');
            $table->unsignedInteger('sub_category_id')->nullable()->index('sub_category_id');
            $table->unsignedInteger('unit_id')->nullable()->index('unit_id');
            $table->unsignedInteger('brand_id')->nullable()->index('brand_id');
            $table->decimal('total_price_change_loss', 15)->nullable()->default(0);
            $table->decimal('total_price_change_gain', 15)->nullable()->default(0);
            $table->string('page_no')->nullable();
            $table->unsignedInteger('user');
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
        Schema::dropIfExists('price_changes_headers');
    }
};
