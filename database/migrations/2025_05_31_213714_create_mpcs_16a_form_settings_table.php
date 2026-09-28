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
        Schema::create('mpcs_16a_form_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->date('date')->nullable();
            $table->time('time')->nullable();
            $table->string('starting_number', 200)->nullable();
            $table->string('ref_pre_form_number', 200)->nullable();
            $table->string('no_of_rows_per_page', 255)->nullable();
            $table->decimal('total_purchase_price_with_vat', 15)->nullable();
            $table->decimal('total_sale_price_with_vat', 15)->nullable();
            $table->unsignedInteger('created_by');
            $table->timestamp('created_at')->useCurrentOnUpdate()->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mpcs_16a_form_settings');
    }
};
