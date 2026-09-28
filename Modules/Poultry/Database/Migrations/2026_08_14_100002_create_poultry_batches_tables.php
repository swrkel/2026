<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The batch is the spine of the module. A batch is a cohort of birds placed on
 * a date into a house, tracked until depletion. Daily records, feed, health,
 * production and cost all hang off it.
 *
 * No foreign key constraints - see the note in the masters migration.
 */
class CreatePoultryBatchesTables extends Migration
{
    public function up()
    {
        Schema::create('poultry_batches', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('farm_id')->index();
            $table->unsignedInteger('house_id')->index();
            $table->unsignedInteger('breed_id')->nullable()->index();

            $table->string('batch_code', 64);

            /*
             * Drives which production screens and which costing treatment apply.
             *   broiler - WIP job: costs accumulate, release at harvest
             *   pullet  - rearing flock, transfers into a layer batch at POL
             *   layer   - amortising asset: rearing cost spread over the cycle
             *   breeder - produces hatching eggs, feeds the hatchery screens
             */
            $table->enum('bird_type', ['broiler', 'layer', 'pullet', 'breeder'])->index();

            $table->date('placement_date')->index();
            $table->unsignedInteger('initial_qty');
            $table->unsignedInteger('male_qty')->default(0);
            $table->unsignedInteger('female_qty')->default(0);

            /*
             * Denormalised running head count. Authoritative source is still
             * initial_qty minus recorded mortality/culls/harvest/transfers;
             * BatchService::recalculateHeadCount() rebuilds it from those rows
             * after any backdated correction.
             */
            $table->integer('current_qty')->default(0);

            // References contacts.id where type in (supplier, both)
            $table->unsignedInteger('supplier_contact_id')->nullable()->index();
            $table->decimal('doc_unit_cost', 22, 4)->default(0);
            $table->decimal('doc_total_cost', 22, 4)->default(0);

            $table->date('expected_depletion_date')->nullable();
            $table->enum('status', ['draft', 'active', 'transferred', 'closed'])
                  ->default('active')->index();
            $table->date('closed_on')->nullable();

            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'batch_code']);
            $table->index(['business_id', 'status', 'bird_type']);
        });

        Schema::create('poultry_batch_transfers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('batch_id')->index();
            $table->unsignedInteger('from_house_id')->nullable()->index();
            $table->unsignedInteger('to_house_id')->nullable()->index();

            /*
             * Set when a pullet batch moves to point of lay and becomes a layer
             * batch. The rearing cost carried on the source batch transfers to
             * the destination batch as its opening capitalised value.
             */
            $table->unsignedInteger('to_batch_id')->nullable()->index();

            $table->date('transfer_date')->index();
            $table->unsignedInteger('qty');
            $table->decimal('cost_transferred', 22, 4)->default(0);
            $table->string('reason')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });

        /*
         * One row per batch per day. The unique key is intentional: staff
         * re-submitting the same day must update, never duplicate. Backdated
         * corrections are expected, so every derived KPI is recomputed from
         * these rows rather than incrementally accumulated.
         */
        Schema::create('poultry_daily_records', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('batch_id')->index();
            $table->date('record_date')->index();

            $table->unsignedInteger('age_days')->default(0);
            $table->unsignedInteger('mortality')->default(0);
            $table->unsignedInteger('culls')->default(0);
            $table->decimal('feed_kg', 15, 4)->default(0);
            $table->decimal('water_litres', 15, 4)->default(0);

            $table->decimal('temperature_c', 6, 2)->nullable();
            $table->decimal('humidity_pct', 6, 2)->nullable();
            $table->decimal('avg_weight_g', 12, 2)->nullable();

            $table->string('mortality_cause')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['batch_id', 'record_date']);
            $table->index(['business_id', 'record_date']);
        });

        Schema::create('poultry_weight_samples', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('batch_id')->index();
            $table->date('sample_date')->index();
            $table->unsignedInteger('age_days')->default(0);
            $table->unsignedInteger('birds_sampled');
            $table->decimal('total_weight_g', 15, 2);
            $table->decimal('avg_weight_g', 12, 2);
            // Coefficient of variation - the standard uniformity measure.
            $table->decimal('uniformity_cv', 8, 2)->nullable();
            $table->decimal('target_weight_g', 12, 2)->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('poultry_weight_samples');
        Schema::dropIfExists('poultry_daily_records');
        Schema::dropIfExists('poultry_batch_transfers');
        Schema::dropIfExists('poultry_batches');
    }
}
