<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('bs_chairs')) {
            Schema::create('bs_chairs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('beauty_branch_id')->nullable()->index();
                $table->string('chair_code')->nullable()->index();
                $table->string('chair_name');
                $table->string('chair_type')->nullable();
                $table->string('floor_name')->nullable();
                $table->string('zone_name')->nullable();
                $table->string('status')->default('available')->index();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }
    public function down(): void { Schema::dropIfExists('bs_chairs'); }
};
