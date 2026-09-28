<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('disnew_final_readiness_checks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('check_code', 100);
            $table->string('check_name');
            $table->string('check_group', 100)->default('general')->index();
            $table->string('status', 30)->default('pending')->index();
            $table->string('severity', 30)->default('info');
            $table->text('message')->nullable();
            $table->unsignedBigInteger('checked_by')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('disnew_installation_verifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable();
            $table->string('verification_key', 120);
            $table->text('verification_value')->nullable();
            $table->string('result', 30)->default('pending');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'verification_key'], 'uq_disnew_install_key_business');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disnew_installation_verifications');
        Schema::dropIfExists('disnew_final_readiness_checks');
    }
};
