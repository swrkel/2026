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
        Schema::create('gold_productions', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->string('reference_no');
            $table->integer('location_id')->index('location_id');
            $table->integer('gold_smith_id')->index('gold_smith_id');
            $table->enum('category_type', ['service', 'non_inventory'])->nullable();
            $table->decimal('product_qty', 10);
            $table->string('receiving_store');
            $table->integer('gold_grade_id')->index('gold_grade_id');
            $table->decimal('wastage_per_8_g', 15, 4);
            $table->decimal('total_product_gold_weight', 15, 4);
            $table->decimal('total_stone_other_weight', 15, 4);
            $table->decimal('wastage_calculation', 15, 4);
            $table->decimal('total_gold_wastage', 15, 4);
            $table->decimal('total_goldsmith_in_g', 15, 4);
            $table->decimal('labour_cost', 15, 4);
            $table->decimal('labour_cost_total', 15, 4);
            $table->decimal('design_cost', 15, 4);
            $table->decimal('stone_cost', 15, 4);
            $table->text('other_cost')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gold_productions');
    }
};
