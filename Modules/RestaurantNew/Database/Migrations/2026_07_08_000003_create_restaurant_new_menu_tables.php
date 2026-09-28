<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rn_menu_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('available_for_dine_in')->default(true);
            $table->boolean('available_for_takeaway')->default(true);
            $table->boolean('available_for_delivery')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['business_id', 'location_id']);
            $table->index(['business_id', 'parent_id']);
        });

        Schema::create('rn_menu_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id')->nullable();
            $table->unsignedBigInteger('menu_category_id');
            $table->unsignedBigInteger('kitchen_section_id')->nullable();
            $table->string('name');
            $table->string('sku', 80)->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 22, 4)->default(0);
            $table->decimal('cost_price', 22, 4)->default(0);
            $table->decimal('tax_percent', 10, 4)->default(0);
            $table->integer('preparation_time_minutes')->default(0);
            $table->string('image_path')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_modifier_required')->default(false);
            $table->boolean('allow_discount')->default(true);
            $table->boolean('track_recipe_stock')->default(false);
            $table->boolean('available_for_dine_in')->default(true);
            $table->boolean('available_for_takeaway')->default(true);
            $table->boolean('available_for_delivery')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['business_id', 'location_id']);
            $table->index(['business_id', 'menu_category_id']);
            $table->index(['business_id', 'sku']);
        });

        Schema::create('rn_menu_item_variants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id')->nullable();
            $table->unsignedBigInteger('menu_item_id');
            $table->string('name');
            $table->string('sku', 80)->nullable();
            $table->decimal('price', 22, 4)->default(0);
            $table->decimal('cost_price', 22, 4)->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['business_id', 'menu_item_id']);
        });

        Schema::create('rn_menu_modifiers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id')->nullable();
            $table->unsignedBigInteger('menu_item_id');
            $table->string('group_name')->nullable();
            $table->string('name');
            $table->decimal('price', 22, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['business_id', 'menu_item_id']);
        });

        Schema::create('rn_menu_recipes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id')->nullable();
            $table->unsignedBigInteger('menu_item_id');
            $table->string('ingredient_name');
            $table->string('ingredient_sku', 80)->nullable();
            $table->string('unit', 50)->nullable();
            $table->decimal('quantity', 22, 4)->default(0);
            $table->decimal('wastage_percent', 10, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['business_id', 'menu_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rn_menu_recipes');
        Schema::dropIfExists('rn_menu_modifiers');
        Schema::dropIfExists('rn_menu_item_variants');
        Schema::dropIfExists('rn_menu_items');
        Schema::dropIfExists('rn_menu_categories');
    }
};
