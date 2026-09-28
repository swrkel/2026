<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('bs_branch_settings')) {
            Schema::create('bs_branch_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('beauty_branch_id')->nullable()->index();
                $table->string('setting_key')->index();
                $table->text('setting_value')->nullable();
                $table->timestamps();
            });
        }
    }
    public function down(): void { Schema::dropIfExists('bs_branch_settings'); }
};
