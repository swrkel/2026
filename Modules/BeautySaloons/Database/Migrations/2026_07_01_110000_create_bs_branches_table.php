<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('bs_branches')) {
            Schema::create('bs_branches', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('business_location_id')->nullable()->index();
                $table->string('branch_code')->nullable()->index();
                $table->string('name');
                $table->string('manager_name')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->text('address')->nullable();
                $table->time('opening_time')->nullable();
                $table->time('closing_time')->nullable();
                $table->boolean('allow_online_booking')->default(false);
                $table->boolean('is_main_branch')->default(false);
                $table->string('status')->default('active')->index();
                $table->timestamps();
            });
        }
    }
    public function down(): void { Schema::dropIfExists('bs_branches'); }
};
