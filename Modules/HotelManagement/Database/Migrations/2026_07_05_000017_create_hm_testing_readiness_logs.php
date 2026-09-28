<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('hm_testing_readiness_logs')) {
            Schema::create('hm_testing_readiness_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('business_location_id')->nullable()->index();
                $table->unsignedBigInteger('checked_by')->nullable();
                $table->string('check_title');
                $table->string('check_status', 50)->default('passed')->index();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hm_testing_readiness_logs');
    }
};
