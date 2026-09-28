<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMissingPetroTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('pump_operator_assignments')) {
            Schema::create('pump_operator_assignments', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->index();
                $table->unsignedInteger('pump_operator_id')->index();
                $table->unsignedInteger('pump_id')->index();
                $table->unsignedInteger('shift_id')->nullable()->index();
                $table->integer('shift_number')->nullable();
                $table->dateTime('date_and_time')->nullable();
                $table->decimal('starting_meter', 20, 3)->default(0);
                $table->decimal('closing_meter', 20, 3)->default(0);
                $table->string('status')->default('open')->index(); // open, close
                $table->boolean('is_confirmed')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pumper_day_entries')) {
            Schema::create('pumper_day_entries', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->index();
                $table->unsignedInteger('pump_operator_id')->index();
                $table->unsignedInteger('pumper_assignment_id')->nullable()->index();
                $table->unsignedInteger('pump_id')->index();
                $table->date('date')->nullable();
                $table->decimal('starting_meter', 20, 3)->default(0);
                $table->decimal('closing_meter', 20, 3)->default(0);
                $table->decimal('testing_ltr', 20, 3)->default(0);
                $table->decimal('sold_ltr', 20, 3)->default(0);
                $table->decimal('amount', 20, 2)->default(0);
                $table->string('settlement_no')->nullable();
                $table->dateTime('settlement_datetime')->nullable();
                $table->unsignedInteger('settlement_added_by')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pumper_day_entries');
        Schema::dropIfExists('pump_operator_assignments');
    }
}
