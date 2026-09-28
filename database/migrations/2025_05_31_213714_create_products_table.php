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
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->index();
            $table->unsignedInteger('business_id')->index('business_id');
            $table->enum('type', ['single', 'variable', 'modifier', 'combo', 'variable_only_in_sale'])->nullable();
            $table->unsignedInteger('unit_id')->nullable()->index();
            $table->text('sub_unit_ids')->nullable();
            $table->unsignedInteger('brand_id')->nullable()->index('brand_id');
            $table->unsignedInteger('category_id')->nullable()->index('category_id');
            $table->unsignedInteger('sub_category_id')->nullable()->index('products_sub_category_id_foreign');
            $table->unsignedInteger('tax')->nullable()->index('products_tax_foreign');
            $table->integer('sale_tax')->nullable();
            $table->enum('tax_type', ['inclusive', 'exclusive']);
            $table->boolean('enable_stock')->default(false);
            $table->boolean('raw_material')->default(false);
            $table->decimal('alert_quantity', 22, 4)->nullable();
            $table->string('sku');
            $table->enum('barcode_type', ['C39', 'C128', 'EAN13', 'EAN8', 'UPCA', 'UPCE'])->nullable()->default('C128');
            $table->decimal('expiry_period', 4)->nullable();
            $table->enum('expiry_period_type', ['days', 'months'])->nullable();
            $table->boolean('enable_sr_no')->default(false);
            $table->string('weight')->nullable();
            $table->string('product_custom_field1')->nullable();
            $table->string('product_custom_field2')->nullable();
            $table->string('product_custom_field3')->nullable();
            $table->string('product_custom_field4')->nullable();
            $table->string('image')->nullable();
            $table->text('product_description')->nullable();
            $table->unsignedInteger('created_by')->index();
            $table->integer('warranty_id')->nullable()->index();
            $table->boolean('is_inactive')->default(false);
            $table->boolean('not_for_selling')->default(false);
            $table->boolean('show_avai_qty_in_qr_catalogue')->default(false);
            $table->boolean('show_in_catalogue_page')->default(false);
            $table->integer('min_sell_price')->default(0);
            $table->boolean('is_medicine')->default(false);
            $table->string('country')->nullable();
            $table->timestamps();
            $table->boolean('multiple_units')->default(false);
            $table->string('stock_type')->nullable();
            $table->unsignedInteger('repair_model_id')->nullable()->index('repair_model_id');
            $table->date('date')->nullable();
            $table->boolean('is_service')->nullable()->default(false);
            $table->integer('semi_finished')->default(0);
            $table->integer('vat_claimed')->default(0);
            $table->text('disabled_in')->nullable();

            $table->index(['brand_id'], 'products_brand_id_foreign');
            $table->index(['business_id']);
            $table->index(['category_id'], 'products_category_id_foreign');
            $table->index(['sub_category_id'], 'sub_category_id');
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
        Schema::dropIfExists('products');
    }
};
