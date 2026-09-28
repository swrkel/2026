<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hatchery operations and batch cost accumulation.
 *
 * poultry_batch_costs is the module's own cost subsidiary ledger. It is NOT a
 * replacement for the ERP's accounting - each row optionally carries the id of
 * the account_transactions row it posted, so Finance stays authoritative for
 * money while this table stays authoritative for cost-per-bird analytics.
 */
class CreatePoultryHatcheryAndCostingTables extends Migration
{
    public function up()
    {
        Schema::create('poultry_hatch_sets', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index();
            // Breeder batch the hatching eggs came from. Null = bought in.
            $table->unsignedInteger('source_batch_id')->nullable()->index();
            $table->unsignedInteger('supplier_contact_id')->nullable()->index();

            $table->string('set_code', 64);
            $table->date('set_date')->index();
            $table->string('setter_no', 32)->nullable();
            $table->unsignedInteger('eggs_set');

            $table->date('candling_date')->nullable();
            $table->unsignedInteger('clear_eggs')->default(0);
            $table->unsignedInteger('fertile_eggs')->default(0);

            $table->date('transfer_date')->nullable();
            $table->string('hatcher_no', 32)->nullable();

            $table->date('hatch_date')->nullable();
            $table->unsignedInteger('chicks_hatched')->default(0);
            $table->unsignedInteger('saleable_chicks')->default(0);
            $table->unsignedInteger('culled_chicks')->default(0);
            $table->unsignedInteger('dead_in_shell')->default(0);

            /*
             * Derived and stored so historical rows keep the figure that was
             * reported at the time. HatcheryService recomputes on edit.
             *   fertility_pct   = fertile_eggs / eggs_set
             *   hatchability_pct = chicks_hatched / fertile_eggs
             *   hatch_of_set_pct = chicks_hatched / eggs_set
             */
            $table->decimal('fertility_pct', 8, 2)->nullable();
            $table->decimal('hatchability_pct', 8, 2)->nullable();
            $table->decimal('hatch_of_set_pct', 8, 2)->nullable();

            $table->enum('status', ['set', 'candled', 'transferred', 'hatched', 'cancelled'])
                  ->default('set')->index();

            $table->unsignedInteger('product_id')->nullable()->index();
            $table->unsignedInteger('variation_id')->nullable()->index();
            $table->unsignedInteger('stock_transaction_id')->nullable()->index();
            $table->boolean('is_posted')->default(false);

            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'set_code']);
            $table->index(['business_id', 'set_date']);
        });

        Schema::create('poultry_batch_costs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('batch_id')->index();
            $table->date('cost_date')->index();

            $table->enum('cost_type', [
                'doc', 'feed', 'medication', 'vaccination', 'labour',
                'utilities', 'litter', 'transport', 'overhead', 'other',
            ])->index();

            $table->decimal('amount', 22, 4)->default(0);

            /*
             * Polymorphic-style pointer back to the row that generated the
             * cost - kept as a plain string + id rather than a morph so that
             * deleting a source row never orphans a constraint.
             * e.g. 'feed_consumption' + poultry_feed_consumptions.id
             */
            $table->string('source_type', 50)->nullable();
            $table->unsignedInteger('source_id')->nullable();

            // account_transactions.id when config('poultry.post_to_ledger')
            $table->unsignedInteger('account_transaction_id')->nullable()->index();
            $table->boolean('is_posted')->default(false)->index();

            $table->string('narration')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['batch_id', 'cost_type']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('poultry_batch_costs');
        Schema::dropIfExists('poultry_hatch_sets');
    }
}
