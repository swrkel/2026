<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestaurantNewNutritionAllergenTables extends Migration
{
    public function up()
    {
        Schema::create('restaurant_new_allergens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->string('name');
            $table->string('code', 80)->nullable()->index();
            $table->string('severity_level', 30)->default('warning')->index();
            $table->boolean('requires_customer_warning')->default(true)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['business_id','name'], 'rn_allergen_name_unique');
        });

        Schema::create('restaurant_new_dietary_tags', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->string('name');
            $table->string('code', 80)->nullable()->index();
            $table->string('tag_type', 50)->default('dietary')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['business_id','name'], 'rn_dietary_tag_name_unique');
        });

        Schema::create('restaurant_new_nutrition_profiles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('menu_item_id')->index();
            $table->decimal('serving_size', 22, 4)->nullable();
            $table->string('serving_unit', 30)->nullable();
            $table->decimal('calories', 22, 4)->default(0);
            $table->decimal('protein_g', 22, 4)->default(0);
            $table->decimal('carbohydrate_g', 22, 4)->default(0);
            $table->decimal('fat_g', 22, 4)->default(0);
            $table->decimal('sugar_g', 22, 4)->default(0);
            $table->decimal('fiber_g', 22, 4)->default(0);
            $table->decimal('sodium_mg', 22, 4)->default(0);
            $table->decimal('cholesterol_mg', 22, 4)->default(0);
            $table->text('nutrition_note')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->unique(['business_id','menu_item_id'], 'rn_nutrition_menu_unique');
        });

        Schema::create('restaurant_new_menu_item_allergens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('menu_item_id')->index();
            $table->unsignedBigInteger('allergen_id')->index();
            $table->boolean('may_contain')->default(false)->index();
            $table->text('warning_note')->nullable();
            $table->timestamps();
            $table->unique(['menu_item_id','allergen_id'], 'rn_menu_allergen_unique');
        });

        Schema::create('restaurant_new_menu_item_dietary_tags', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('menu_item_id')->index();
            $table->unsignedBigInteger('dietary_tag_id')->index();
            $table->timestamps();
            $table->unique(['menu_item_id','dietary_tag_id'], 'rn_menu_diet_tag_unique');
        });

        Schema::create('restaurant_new_recipe_compliance_checks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('menu_item_id')->nullable()->index();
            $table->unsignedBigInteger('recipe_id')->nullable()->index();
            $table->string('check_type', 60)->index();
            $table->string('status', 40)->default('pending')->index();
            $table->text('finding')->nullable();
            $table->text('corrective_action')->nullable();
            $table->unsignedBigInteger('checked_by')->nullable()->index();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('restaurant_new_customer_allergy_warnings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('menu_item_id')->nullable()->index();
            $table->unsignedBigInteger('allergen_id')->nullable()->index();
            $table->string('warning_status', 40)->default('shown')->index();
            $table->unsignedBigInteger('acknowledged_by')->nullable()->index();
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('warning_message')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('restaurant_new_customer_allergy_warnings');
        Schema::dropIfExists('restaurant_new_recipe_compliance_checks');
        Schema::dropIfExists('restaurant_new_menu_item_dietary_tags');
        Schema::dropIfExists('restaurant_new_menu_item_allergens');
        Schema::dropIfExists('restaurant_new_nutrition_profiles');
        Schema::dropIfExists('restaurant_new_dietary_tags');
        Schema::dropIfExists('restaurant_new_allergens');
    }
}
