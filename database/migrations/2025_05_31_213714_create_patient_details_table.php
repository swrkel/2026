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
        Schema::create('patient_details', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user_id')->index('user_id');
            $table->string('name')->nullable();
            $table->string('address')->nullable();
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('mobile', 15)->nullable();
            $table->string('date_of_birth', 20)->nullable();
            $table->string('gender', 15)->nullable();
            $table->integer('marital_status')->nullable();
            $table->integer('blood_group')->nullable();
            $table->string('height', 10)->nullable();
            $table->string('weight', 10)->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('time_zone')->nullable();
            $table->string('profile_image')->nullable();
            $table->string('known_allergies')->nullable();
            $table->string('notes')->nullable();
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
        Schema::dropIfExists('patient_details');
    }
};
