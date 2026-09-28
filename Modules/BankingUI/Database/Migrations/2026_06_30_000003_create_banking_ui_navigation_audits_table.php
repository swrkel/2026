<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('banking_ui_navigation_audits')) {
            Schema::create('banking_ui_navigation_audits', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('module_key')->nullable()->index();
                $table->string('route_name')->nullable()->index();
                $table->text('url')->nullable();
                $table->string('ip_address', 100)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('banking_ui_navigation_audits');
    }
};
