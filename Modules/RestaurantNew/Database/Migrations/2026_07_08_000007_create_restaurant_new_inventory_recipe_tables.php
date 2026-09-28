<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_new_ingredient_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'location_id', 'name'], 'restnew_ing_cat_unique');
        });

        Schema::create('restaurant_new_ingredients', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('unit', 30)->default('unit');
            $table->decimal('purchase_price', 22, 4)->default(0);
            $table->decimal('reorder_level', 22, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'location_id', 'sku']);
        });

        Schema::create('restaurant_new_ingredient_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->index();
            $table->unsignedBigInteger('ingredient_id')->index();
            $table->decimal('quantity', 22, 4)->default(0);
            $table->decimal('average_cost', 22, 4)->default(0);
            $table->decimal('stock_value', 22, 4)->default(0);
            $table->timestamps();
            $table->unique(['business_id', 'location_id', 'ingredient_id'], 'restnew_ing_stock_unique');
        });

        Schema::create('restaurant_new_recipes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('menu_item_id')->index();
            $table->string('name');
            $table->decimal('yield_qty', 22, 4)->default(1);
            $table->string('yield_unit', 30)->default('portion');
            $table->decimal('estimated_cost', 22, 4)->default(0);
            $table->text('preparation_notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('restaurant_new_recipe_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedBigInteger('recipe_id')->index();
            $table->unsignedBigInteger('ingredient_id')->index();
            $table->decimal('quantity', 22, 4)->default(0);
            $table->string('unit', 30)->default('unit');
            $table->decimal('unit_cost', 22, 4)->default(0);
            $table->decimal('line_cost', 22, 4)->default(0);
            $table->boolean('is_optional')->default(false);
            $table->timestamps();
        });

        Schema::create('restaurant_new_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->index();
            $table->unsignedBigInteger('ingredient_id')->index();
            $table->string('movement_type', 40)->index();
            $table->string('reference_type', 80)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('quantity_in', 22, 4)->default(0);
            $table->decimal('quantity_out', 22, 4)->default(0);
            $table->decimal('balance_qty', 22, 4)->default(0);
            $table->decimal('unit_cost', 22, 4)->default(0);
            $table->decimal('total_cost', 22, 4)->default(0);
            $table->string('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('restaurant_new_wastages', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->index();
            $table->unsignedBigInteger('ingredient_id')->index();
            $table->date('wastage_date')->index();
            $table->decimal('quantity', 22, 4)->default(0);
            $table->string('unit', 30)->default('unit');
            $table->decimal('unit_cost', 22, 4)->default(0);
            $table->decimal('total_cost', 22, 4)->default(0);
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_new_wastages');
        Schema::dropIfExists('restaurant_new_stock_movements');
        Schema::dropIfExists('restaurant_new_recipe_lines');
        Schema::dropIfExists('restaurant_new_recipes');
        Schema::dropIfExists('restaurant_new_ingredient_stocks');
        Schema::dropIfExists('restaurant_new_ingredients');
        Schema::dropIfExists('restaurant_new_ingredient_categories');
    }
};
