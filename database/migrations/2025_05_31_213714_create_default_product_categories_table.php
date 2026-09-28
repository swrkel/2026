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
        Schema::create('default_product_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->string('short_code')->nullable();
            $table->integer('parent_id')->index('parent_id');
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
        Schema::dropIfExists('default_product_categories');
    }
};
