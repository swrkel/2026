<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTailoringFeatureSettingsTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('tailoring_feature_settings')) {
            Schema::create('tailoring_feature_settings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->string('edition', 30)->default('basic')->index();
                $table->json('enabled_features')->nullable();
                $table->json('menu_visibility')->nullable();
                $table->json('setup_wizard_answers')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }
    public function down() { Schema::dropIfExists('tailoring_feature_settings'); }
}
