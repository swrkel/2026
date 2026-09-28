<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('disnew_final_readiness_runs')) {
            Schema::create('disnew_final_readiness_runs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->string('status', 50)->default('pending')->index();
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('run_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('disnew_final_readiness_runs');
    }
};
