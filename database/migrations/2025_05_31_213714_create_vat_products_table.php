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
        Schema::create('vat_products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->index('products_name_index');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->enum('type', ['single', 'variable', 'modifier', 'combo', 'variable_only_in_sale'])->nullable();
            $table->unsignedInteger('unit_id')->nullable()->index('products_unit_id_index');
            $table->unsignedInteger('tax')->nullable()->index('products_tax_foreign');
            $table->integer('sale_tax')->nullable();
            $table->enum('tax_type', ['inclusive', 'exclusive']);
            $table->boolean('raw_material')->default(false);
            $table->string('sku');
            $table->string('image')->nullable();
            $table->text('product_description')->nullable();
            $table->unsignedInteger('created_by')->index('products_created_by_index');
            $table->integer('warranty_id')->nullable()->index('products_warranty_id_index');
            $table->boolean('is_inactive')->default(false);
            $table->integer('min_sell_price')->default(0);
            $table->timestamps();
            $table->boolean('multiple_units')->default(false);
            $table->date('date')->nullable();
            $table->integer('semi_finished')->default(0);
            $table->integer('vat_claimed')->default(0);

            $table->index(['business_id'], 'products_business_id_index');
            $table->index(['unit_id'], 'unit_id');
            $table->index(['warranty_id'], 'warranty_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vat_products');
    }
};
