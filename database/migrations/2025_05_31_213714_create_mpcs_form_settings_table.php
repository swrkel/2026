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
        Schema::create('mpcs_form_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->integer('F9C_sn')->nullable();
            $table->date('F9C_tdate')->nullable();
            $table->integer('F159ABC_form_sn')->nullable();
            $table->date('F159ABC_form_tdate')->nullable();
            $table->boolean('F159ABC_first_day_after_stock_taking')->default(false);
            $table->boolean('F159ABC_first_day_of_next_month')->default(false);
            $table->integer('F159ABC_first_day_of_next_month_selected')->nullable();
            $table->integer('F16A_form_sn')->nullable();
            $table->date('F16A_form_tdate')->nullable();
            $table->integer('F21C_form_sn')->nullable();
            $table->date('F21C_form_tdate')->nullable();
            $table->integer('F14_form_sn')->nullable();
            $table->date('F14_form_tdate')->nullable();
            $table->integer('F17_form_sn')->nullable();
            $table->date('F17_form_tdate')->nullable();
            $table->integer('F20_form_sn')->nullable();
            $table->date('F20_form_tdate')->nullable();
            $table->integer('F21_form_sn')->nullable();
            $table->date('F21_form_tdate')->nullable();
            $table->integer('F22_form_sn')->nullable();
            $table->date('F22_form_tdate')->nullable();
            $table->integer('F22_no_of_product_per_page')->nullable();
            $table->boolean('current_stock_aa_onstocktaking')->nullable()->default(false);
            $table->boolean('f16a_first_day_after_stock_taking')->default(false);
            $table->boolean('f16a_first_day_of_next_month')->default(false);
            $table->integer('f16a_first_day_of_next_month_selected')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->decimal('F16A_total_pp', 15)->default(0);
            $table->decimal('F16A_total_sp', 15)->default(0);
            $table->boolean('F21C_first_day_after_stock_taking')->default(false);
            $table->integer('F21C_first_day_of_next_month_selected')->nullable();
            $table->boolean('F21C_first_day_of_next_month')->default(false);
            $table->boolean('F9C_first_day_after_stock_taking')->default(false);
            $table->integer('F9C_first_day_of_next_month_selected')->nullable();
            $table->boolean('F9C_first_day_of_next_month')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mpcs_form_settings');
    }
};
