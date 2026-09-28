<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('myhealth_allergies')) {
            Schema::create('myhealth_allergies', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('member_id')->index();
                $table->string('allergy_name');
                $table->string('allergy_type')->nullable();
                $table->string('severity')->nullable();
                $table->text('reaction')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('myhealth_chronic_conditions')) {
            Schema::create('myhealth_chronic_conditions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('member_id')->index();
                $table->string('condition_name');
                $table->date('diagnosed_date')->nullable();
                $table->string('severity')->nullable();
                $table->string('status')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('myhealth_clinical_alerts')) {
            Schema::create('myhealth_clinical_alerts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('member_id')->index();
                $table->unsignedBigInteger('consultation_id')->nullable()->index();
                $table->string('alert_type')->index();
                $table->string('severity')->default('medium')->index();
                $table->string('title');
                $table->text('message')->nullable();
                $table->string('source')->nullable();
                $table->string('status')->default('open')->index();
                $table->unsignedBigInteger('acknowledged_by')->nullable();
                $table->timestamp('acknowledged_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('myhealth_clinical_alerts');
        Schema::dropIfExists('myhealth_chronic_conditions');
        Schema::dropIfExists('myhealth_allergies');
    }
};
