<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('bs_branch_holidays')) {
            Schema::create('bs_branch_holidays', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('beauty_branch_id')->nullable()->index();
                $table->date('holiday_date')->index();
                $table->string('title');
                $table->boolean('full_day')->default(true);
                $table->time('from_time')->nullable();
                $table->time('to_time')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }
    public function down(): void { Schema::dropIfExists('bs_branch_holidays'); }
};
