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
        Schema::create('dip_resettings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('location_id')->index('location_id');
            $table->unsignedInteger('tank_id')->index('tank_id');
            $table->string('meter_reset_form_no');
            $table->string('date_and_time');
            $table->date('transaction_date');
            $table->decimal('system_dip_balance', 15);
            $table->decimal('current_dip_difference', 15);
            $table->decimal('reset_new_dip', 15);
            $table->text('reason')->nullable();
            $table->unsignedInteger('adjustment_transaction_id')->nullable()->index('adjustment_transaction_id');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->decimal('current_qty', 15);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dip_resettings');
    }
};
