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
        if (Schema::hasTable('pump_operator_assignments')) {
            return;
        }

        Schema::create('pump_operator_assignments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('pump_id')->index('pump_id');
            $table->unsignedInteger('pump_operator_id')->index('pump_operator_id');
            $table->decimal('starting_meter', 15, 6)->default(0);
            $table->decimal('closing_meter', 15, 6)->nullable()->default(0);
            $table->timestamp('date_and_time')->nullable();
            $table->timestamp('close_date_and_time')->nullable();
            $table->enum('status', ['open', 'close'])->default('open');
            $table->unsignedInteger('settlement_id')->nullable()->index('settlement_id');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->integer('assigned_by')->nullable();
            $table->integer('is_confirmed')->default(0);
            $table->timestamp('confirmed_at')->nullable();
            $table->integer('is_manually_closed')->default(0);
            $table->integer('pump_operator_other_sale_id')->nullable()->index('pump_operator_other_sale_id');
            $table->integer('closed_in_settlement')->default(0);
            $table->integer('shift_id')->nullable()->index('shift_id');
            $table->integer('shift_number')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pump_operator_assignments');
    }
};
