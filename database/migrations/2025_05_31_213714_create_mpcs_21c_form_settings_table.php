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
        Schema::create('mpcs_21c_form_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->date('date');
            $table->time('time');
            $table->string('starting_number', 255);
            $table->string('ref_pre_form_number', 255);
            $table->decimal('rec_sec_prev_day_amt', 10);
            $table->decimal('rec_sec_opn_stock_amt', 10);
            $table->decimal('issue_section_previous_day_amount', 10);
            $table->string('manager_name', 255);
            $table->longText('categories')->nullable();
            $table->longText('pumps')->nullable();
            $table->longText('meters')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamp('created_at')->useCurrentOnUpdate()->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mpcs_21c_form_settings');
    }
};
