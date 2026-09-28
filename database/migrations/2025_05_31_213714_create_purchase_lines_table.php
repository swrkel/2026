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
        Schema::create('purchase_lines', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('transaction_id')->index('purchase_lines_transaction_id_foreign');
            $table->unsignedInteger('product_id')->index('product_id');
            $table->unsignedInteger('variation_id')->index('purchase_lines_variation_id_foreign');
            $table->decimal('quantity', 22, 6)->default(0);
            $table->decimal('bonus_qty', 15, 6)->nullable()->default(0);
            $table->decimal('pp_without_discount', 31, 6)->default(0)->comment('Purchase price before inline discounts');
            $table->decimal('discount_percent', 15, 6)->default(0)->comment('Inline discount percentage');
            $table->decimal('purchase_price', 31, 6);
            $table->decimal('purchase_price_inc_tax', 31, 6)->default(0);
            $table->decimal('item_tax', 31, 6)->comment('Tax for one quantity');
            $table->unsignedInteger('tax_id')->nullable()->index('purchase_lines_tax_id_foreign');
            $table->decimal('quantity_sold', 22, 6)->default(0)->comment('Quanity sold from 

this purchase line');
            $table->decimal('quantity_adjusted', 22, 6)->default(0)->comment('Quanity adjusted in stock 

adjustment from this purchase line');
            $table->decimal('quantity_returned', 22, 6)->default(0);
            $table->decimal('mfg_quantity_used', 22, 6)->default(0);
            $table->date('mfg_date')->nullable();
            $table->date('exp_date')->nullable();
            $table->string('lot_number', 256)->nullable()->index();
            $table->integer('sub_unit_id')->nullable()->index();
            $table->integer('secondary_unit_quantity');
            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('new_deleted_at')->nullable();
            $table->integer('new_deleted_by')->nullable();

            $table->index(['product_id'], 'purchase_lines_product_id_foreign');
            $table->index(['sub_unit_id'], 'sub_unit_id');
            $table->index(['tax_id'], 'tax_id');
            $table->index(['transaction_id'], 'transaction_id');
            $table->index(['variation_id'], 'variation_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_lines');
    }
};
