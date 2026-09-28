<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('disnew_production_exceptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->string('exception_type', 80)->index();
            $table->string('reference_no')->nullable()->index();
            $table->text('message')->nullable();
            $table->string('status', 30)->default('open')->index();
            $table->unsignedInteger('assigned_to')->nullable()->index();
            $table->text('resolution_note')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });
        Schema::create('disnew_production_audits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->string('entity_type', 80)->index();
            $table->unsignedBigInteger('entity_id')->nullable()->index();
            $table->string('action', 80)->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->timestamps();
        });
        Schema::create('disnew_super_admin_monitors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index();
            $table->string('metric_key', 80)->index();
            $table->decimal('metric_value', 20, 4)->default(0);
            $table->date('snapshot_date')->index();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('disnew_super_admin_monitors');
        Schema::dropIfExists('disnew_production_audits');
        Schema::dropIfExists('disnew_production_exceptions');
    }
};
