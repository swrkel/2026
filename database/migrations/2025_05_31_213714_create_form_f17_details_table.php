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
        Schema::create('form_f17_details', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('header_id')->index('header_id');
            $table->integer('product_id')->index('product_id');
            $table->string('sku')->nullable();
            $table->string('product')->nullable();
            $table->decimal('current_stock', 15, 5)->default(0);
            $table->decimal('unit_price', 15)->nullable();
            $table->enum('select_mode', ['increase', 'decrease'])->nullable();
            $table->decimal('new_price', 15)->default(0);
            $table->decimal('unit_price_difference', 15)->default(0);
            $table->decimal('price_changed_loss', 15)->default(0);
            $table->decimal('price_changed_gain', 15)->default(0);
            $table->integer('page_no')->nullable();
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
        Schema::dropIfExists('form_f17_details');
    }
};
