<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('restnew_haccp_temperature_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('business_location_id')->nullable()->index();
            $table->string('asset_name');
            $table->string('check_type', 100);
            $table->decimal('temperature', 10, 3);
            $table->decimal('min_temperature', 10, 3)->nullable();
            $table->decimal('max_temperature', 10, 3)->nullable();
            $table->string('status', 50)->default('ok')->index();
            $table->dateTime('checked_at')->index();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('restnew_haccp_corrective_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('business_location_id')->nullable()->index();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority', 50)->default('normal')->index();
            $table->string('status', 50)->default('open')->index();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->dateTime('due_at')->nullable()->index();
            $table->dateTime('completed_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restnew_haccp_corrective_actions');
        Schema::dropIfExists('restnew_haccp_temperature_logs');
    }
};
