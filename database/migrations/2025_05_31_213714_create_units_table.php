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
        Schema::create('units', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->string('actual_name');
            $table->string('short_name');
            $table->boolean('allow_decimal');
            $table->integer('base_unit_id')->nullable()->index('base_unit_id');
            $table->decimal('base_unit_multiplier', 20, 4)->nullable();
            $table->boolean('is_property')->default(false);
            $table->boolean('show_in_add_product_unit')->default(false);
            $table->boolean('show_in_add_pos_unit')->default(false);
            $table->boolean('show_in_add_sale_unit')->default(false);
            $table->boolean('show_in_add_project_unit')->default(false);
            $table->boolean('show_in_sell_land_block_unit')->default(false);
            $table->unsignedInteger('created_by')->index('units_created_by_foreign');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['base_unit_id']);
            $table->index(['business_id'], 'units_business_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('units');
    }
};
