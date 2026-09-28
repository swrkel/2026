<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Poultry master data.
 *
 * NOTE ON FOREIGN KEYS: this module deliberately declares no foreign key
 * constraints, to any table - core or its own. Cross-module FKs would couple
 * the module's migrations to the presence of core tables, and even internal
 * FKs make partial restores and tenant-level data surgery painful on an
 * install of this size. Referential integrity is enforced in the service
 * layer. Indexes are still declared everywhere a FK would have been.
 */
class CreatePoultryMastersTables extends Migration
{
    public function up()
    {
        Schema::create('poultry_farms', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index();
            // References business_locations.id - the ERP site this farm sits at.
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->string('name');
            $table->string('code', 32)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            // References users.id
            $table->unsignedInteger('manager_user_id')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'code']);
        });

        Schema::create('poultry_houses', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('farm_id')->index();
            $table->string('name');
            $table->string('code', 32)->nullable();
            $table->enum('housing_type', ['deep_litter', 'cage', 'free_range', 'slatted', 'breeder'])
                  ->default('deep_litter');
            $table->unsignedInteger('capacity')->default(0);
            $table->decimal('floor_area_sqm', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'farm_id']);
        });

        Schema::create('poultry_breeds', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index();
            $table->string('name');
            $table->enum('bird_type', ['broiler', 'layer', 'breeder', 'dual_purpose'])->index();
            /*
             * Performance standard for this breed, held as JSON so a hatchery's
             * published curve can be loaded without a schema change. Shape:
             * {"weights":{"7":180,"14":450},"hen_day":{"20":45,"25":88},
             *  "target_fcr":1.55,"depletion_day":42}
             * Keys are age in days for weights, week of lay for hen_day.
             */
            $table->text('standard_curve')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'bird_type']);
        });

        Schema::create('poultry_egg_grades', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index();
            $table->string('name', 64);
            $table->string('code', 16)->nullable();
            $table->decimal('min_weight_g', 8, 2)->nullable();
            $table->decimal('max_weight_g', 8, 2)->nullable();
            /*
             * Links this grade to a sellable variation in the shared products /
             * variations tables. When set, collecting eggs of this grade adds
             * stock the POS and Distribution modules can sell with no new code.
             * Null means the grade is tracked but not stocked (e.g. broken).
             */
            $table->unsignedInteger('product_id')->nullable()->index();
            $table->unsignedInteger('variation_id')->nullable()->index();
            $table->boolean('is_saleable')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'is_saleable']);
        });

        Schema::create('poultry_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index();
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'key']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('poultry_settings');
        Schema::dropIfExists('poultry_egg_grades');
        Schema::dropIfExists('poultry_breeds');
        Schema::dropIfExists('poultry_houses');
        Schema::dropIfExists('poultry_farms');
    }
}
