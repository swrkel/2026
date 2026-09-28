<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('leads_new_sources')) {
            Schema::create('leads_new_sources', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->string('name');
                $table->string('color', 20)->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('leads_new_statuses')) {
            Schema::create('leads_new_statuses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->string('name');
                $table->string('color', 20)->nullable();
                $table->boolean('is_final')->default(false);
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('leads_new_priorities')) {
            Schema::create('leads_new_priorities', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->string('name');
                $table->string('color', 20)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('leads_new_campaigns')) {
            Schema::create('leads_new_campaigns', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->string('name');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->decimal('budget', 18, 4)->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('leads_new_territories')) {
            Schema::create('leads_new_territories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('leads_new_activities')) {
            Schema::create('leads_new_activities', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('lead_id')->nullable()->index();
                $table->unsignedBigInteger('opportunity_id')->nullable()->index();
                $table->string('type')->default('note')->index();
                $table->string('title')->nullable();
                $table->text('description')->nullable();
                $table->dateTime('activity_date')->nullable()->index();
                $table->unsignedBigInteger('assigned_to')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leads_new_activities');
        Schema::dropIfExists('leads_new_territories');
        Schema::dropIfExists('leads_new_campaigns');
        Schema::dropIfExists('leads_new_priorities');
        Schema::dropIfExists('leads_new_statuses');
        Schema::dropIfExists('leads_new_sources');
    }
};
