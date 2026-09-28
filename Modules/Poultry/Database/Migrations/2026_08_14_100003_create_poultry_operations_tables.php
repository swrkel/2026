<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daily operations: feed consumption, egg collection, health, harvest.
 *
 * Feed, medication, eggs and live birds are all rows in the SHARED products /
 * variations tables - this module never duplicates them. What it stores here
 * is the link between a batch and those shared records, plus the reference to
 * the stock transaction it posted so a correction can reverse cleanly.
 */
class CreatePoultryOperationsTables extends Migration
{
    public function up()
    {
        Schema::create('poultry_feed_consumptions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('batch_id')->index();
            $table->date('consumption_date')->index();

            // Shared products / variations / business_locations
            $table->unsignedInteger('product_id')->index();
            $table->unsignedInteger('variation_id')->index();
            $table->unsignedInteger('location_id')->index();

            $table->decimal('qty', 15, 4);
            $table->decimal('unit_cost', 22, 4)->default(0);
            $table->decimal('total_cost', 22, 4)->default(0);

            /*
             * transactions.id of the poultry_feed_issue row this posted, or
             * null when config('poultry.post_to_stock') is false. Reversing a
             * consumption reverses this transaction rather than editing stock.
             */
            $table->unsignedInteger('stock_transaction_id')->nullable()->index();
            $table->boolean('is_posted')->default(false)->index();

            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'consumption_date']);
        });

        Schema::create('poultry_egg_collections', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('batch_id')->index();
            $table->date('collection_date')->index();

            // Layers are collected several times a day; slot keeps them apart.
            $table->enum('slot', ['morning', 'midday', 'afternoon', 'evening'])
                  ->default('morning');

            $table->unsignedInteger('grade_id')->index();
            $table->unsignedInteger('qty');
            $table->decimal('weight_kg', 15, 4)->nullable();

            /*
             * Set when the grade maps to a saleable variation and production
             * was posted to shared stock as a poultry_production transaction.
             */
            $table->unsignedInteger('stock_transaction_id')->nullable()->index();
            $table->boolean('is_posted')->default(false)->index();

            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['batch_id', 'collection_date', 'slot', 'grade_id'], 'poultry_egg_unique_slot_grade');
            $table->index(['business_id', 'collection_date']);
        });

        Schema::create('poultry_vaccination_schedules', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index();
            // Null breed = applies to every breed of this bird_type.
            $table->unsignedInteger('breed_id')->nullable()->index();
            $table->enum('bird_type', ['broiler', 'layer', 'pullet', 'breeder', 'all'])->default('all');

            $table->string('name');
            $table->string('disease')->nullable();
            // Shared products row for the vaccine, when it is stocked.
            $table->unsignedInteger('product_id')->nullable()->index();
            $table->unsignedInteger('age_days');
            $table->enum('route', ['drinking_water', 'eye_drop', 'spray', 'injection_sc', 'injection_im', 'wing_web', 'beak_dip'])
                  ->default('drinking_water');
            $table->string('dose')->nullable();
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'bird_type', 'age_days']);
        });

        Schema::create('poultry_vaccination_records', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('batch_id')->index();
            $table->unsignedInteger('schedule_id')->nullable()->index();

            $table->string('name');
            $table->unsignedInteger('product_id')->nullable()->index();
            $table->unsignedInteger('variation_id')->nullable()->index();
            $table->date('administered_on')->index();
            $table->unsignedInteger('age_days')->default(0);
            $table->unsignedInteger('birds_covered')->default(0);
            $table->string('dose')->nullable();
            $table->string('route')->nullable();
            $table->string('vaccine_batch_no')->nullable();
            $table->string('administered_by')->nullable();
            $table->decimal('qty_used', 15, 4)->default(0);
            $table->decimal('total_cost', 22, 4)->default(0);
            $table->unsignedInteger('stock_transaction_id')->nullable()->index();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });

        /*
         * Medication with a withdrawal period. withdrawal_until is the gate
         * that WithdrawalGuard checks before eggs or meat from this batch may
         * be sold - a food safety requirement, not a convenience field.
         */
        Schema::create('poultry_treatments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('batch_id')->index();

            $table->string('name');
            $table->unsignedInteger('product_id')->nullable()->index();
            $table->unsignedInteger('variation_id')->nullable()->index();
            $table->string('diagnosis')->nullable();
            $table->date('started_on')->index();
            $table->date('ended_on')->nullable();
            $table->string('dosage')->nullable();
            $table->string('route')->nullable();

            $table->unsignedInteger('withdrawal_days')->default(0);
            $table->date('withdrawal_until')->nullable()->index();

            $table->string('vet_name')->nullable();
            $table->decimal('qty_used', 15, 4)->default(0);
            $table->decimal('total_cost', 22, 4)->default(0);
            $table->unsignedInteger('stock_transaction_id')->nullable()->index();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'withdrawal_until']);
        });

        Schema::create('poultry_harvests', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('batch_id')->index();
            $table->date('harvest_date')->index();

            $table->enum('harvest_type', ['full', 'partial', 'cull_sale', 'spent_hen'])->default('partial');
            $table->unsignedInteger('birds_qty');
            $table->decimal('total_weight_kg', 15, 4)->default(0);
            $table->decimal('avg_weight_kg', 12, 4)->default(0);

            // Shared contacts row, type in (customer, both)
            $table->unsignedInteger('buyer_contact_id')->nullable()->index();
            // Shared variation the live weight was produced into, if stocked.
            $table->unsignedInteger('product_id')->nullable()->index();
            $table->unsignedInteger('variation_id')->nullable()->index();

            $table->decimal('rate_per_kg', 22, 4)->default(0);
            $table->decimal('total_value', 22, 4)->default(0);

            $table->unsignedInteger('stock_transaction_id')->nullable()->index();
            $table->boolean('is_posted')->default(false)->index();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('poultry_harvests');
        Schema::dropIfExists('poultry_treatments');
        Schema::dropIfExists('poultry_vaccination_records');
        Schema::dropIfExists('poultry_vaccination_schedules');
        Schema::dropIfExists('poultry_egg_collections');
        Schema::dropIfExists('poultry_feed_consumptions');
    }
}
