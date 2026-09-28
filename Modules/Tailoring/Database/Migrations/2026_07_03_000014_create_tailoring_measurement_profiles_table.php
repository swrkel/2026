<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTailoringMeasurementProfilesTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('tailoring_measurement_profiles')) {
            Schema::create('tailoring_measurement_profiles', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->string('profile_name');
                $table->string('garment_type')->nullable()->index();
                $table->json('measurements')->nullable();
                $table->json('style_preferences')->nullable();
                $table->text('fitting_notes')->nullable();
                $table->unsignedInteger('version_no')->default(1);
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }
    public function down() { Schema::dropIfExists('tailoring_measurement_profiles'); }
}
