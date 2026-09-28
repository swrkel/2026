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
        Schema::create('categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->string('short_code')->nullable();
            $table->integer('parent_id')->index('parent_id');
            $table->unsignedBigInteger('default_product_category_id')->nullable()->index('default_product_category_id');
            $table->string('vat_exempted', 10)->default('No');
            $table->string('category_type')->nullable();
            $table->enum('add_related_account', ['category_level', 'sub_category_level'])->nullable();
            $table->integer('cogs_account_id')->nullable()->index('cogs_account_id');
            $table->integer('sales_income_account_id')->nullable()->index('sales_income_account_id');
            $table->boolean('weight_excess_loss_applicable')->default(false);
            $table->unsignedInteger('weight_loss_expense_account_id')->nullable()->index('weight_loss_expense_account_id');
            $table->unsignedInteger('weight_excess_income_account_id')->nullable()->index('weight_excess_income_account_id');
            $table->unsignedInteger('created_by')->index('categories_created_by_foreign');
            $table->softDeletes();
            $table->timestamps();
            $table->text('description')->nullable();
            $table->string('remaining_stock_adjusts', 10)->nullable();
            $table->integer('price_increment_acc')->nullable();
            $table->integer('price_reduction_acc')->nullable();
            $table->string('nic', 100)->nullable();
            $table->string('vat_based_on', 20)->default('sale_price');
            $table->string('apply_vat_on')->default('on_product_sub_category_settings');
            $table->integer('vat_not_applicable');
            $table->decimal('profit_percentage', 10, 5)->nullable()->default(0);

            $table->index(['business_id'], 'categories_business_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('categories');
    }
};
