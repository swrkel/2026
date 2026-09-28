<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('communication_hub_campaigns')) {
            Schema::create('communication_hub_campaigns', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('channel', 30)->default('sms');
                $table->unsignedBigInteger('template_id')->nullable();
                $table->string('audience_type', 50)->default('manual');
                $table->json('audience_filters')->nullable();
                $table->string('status', 30)->default('draft');
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->unsignedInteger('total_recipients')->default(0);
                $table->unsignedInteger('sent_count')->default(0);
                $table->unsignedInteger('failed_count')->default(0);
                $table->decimal('estimated_cost', 15, 4)->default(0);
                $table->decimal('actual_cost', 15, 4)->default(0);
                $table->text('description')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_hub_campaigns');
    }
};
