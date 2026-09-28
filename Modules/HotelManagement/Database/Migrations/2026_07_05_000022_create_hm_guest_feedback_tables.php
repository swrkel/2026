<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hm_guest_feedback_questions')) {
            Schema::create('hm_guest_feedback_questions', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('business_location_id')->nullable()->index();
                $table->string('question', 255);
                $table->string('category', 60)->default('general')->index();
                $table->unsignedTinyInteger('rating_scale')->default(5);
                $table->unsignedInteger('display_order')->default(0);
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('hm_guest_feedback')) {
            Schema::create('hm_guest_feedback', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('business_location_id')->nullable()->index();
                $table->unsignedBigInteger('guest_id')->nullable()->index();
                $table->unsignedBigInteger('reservation_id')->nullable()->index();
                $table->unsignedBigInteger('folio_id')->nullable()->index();
                $table->date('feedback_date')->nullable()->index();
                $table->string('source', 60)->default('front_desk')->index();
                $table->decimal('overall_rating', 6, 2)->default(0);
                $table->decimal('room_rating', 6, 2)->nullable();
                $table->decimal('service_rating', 6, 2)->nullable();
                $table->decimal('food_rating', 6, 2)->nullable();
                $table->decimal('cleanliness_rating', 6, 2)->nullable();
                $table->text('comments')->nullable();
                $table->string('status', 30)->default('open')->index();
                $table->text('resolution_note')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->unsignedInteger('resolved_by')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['business_id', 'business_location_id', 'feedback_date'], 'hm_feedback_scope_date_idx');
                $table->index(['business_id', 'business_location_id', 'status'], 'hm_feedback_scope_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hm_guest_feedback');
        Schema::dropIfExists('hm_guest_feedback_questions');
    }
};
