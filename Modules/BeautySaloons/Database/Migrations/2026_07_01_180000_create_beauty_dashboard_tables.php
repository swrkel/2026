<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('beauty_dashboard_snapshots')) {
            Schema::create('beauty_dashboard_snapshots', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('business_location_id')->nullable()->index();
                $table->string('snapshot_type')->index();
                $table->date('snapshot_date')->index();
                $table->json('payload')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'business_location_id', 'snapshot_type', 'snapshot_date'], 'beauty_dash_snapshot_unique');
            });
        }

        if (!Schema::hasTable('beauty_dashboard_widgets')) {
            Schema::create('beauty_dashboard_widgets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('dashboard_type')->index();
                $table->string('widget_key')->index();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_visible')->default(true);
                $table->json('settings')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('beauty_dashboard_widgets');
        Schema::dropIfExists('beauty_dashboard_snapshots');
    }
};
