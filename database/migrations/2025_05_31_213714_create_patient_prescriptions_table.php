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
        Schema::create('patient_prescriptions', function (Blueprint $table) {
            $table->integer('id', true);
            $table->date('date')->nullable();
            $table->unsignedInteger('business_id')->index('business_id');
            $table->string('hospital_name')->nullable();
            $table->integer('doctor_id')->index('doctor_id');
            $table->float('amount', 10, 0)->nullable();
            $table->string('symptoms');
            $table->string('diagnosis');
            $table->integer('allergies_id')->index('allergies_id');
            $table->dateTime('prescription_date');
            $table->text('prescription_file')->nullable();
            $table->string('bill_file_dummy')->nullable();
            $table->boolean('is_upload')->default(false);
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
        Schema::dropIfExists('patient_prescriptions');
    }
};
