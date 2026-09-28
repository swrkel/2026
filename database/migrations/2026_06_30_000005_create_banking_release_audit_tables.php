<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('banking_ui_release_checks', function (Blueprint $table) {
            $table->id();
            $table->string('module_code')->index();
            $table->string('check_key')->index();
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('checked_by')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('banking_ui_release_issues', function (Blueprint $table) {
            $table->id();
            $table->string('module_code')->index();
            $table->string('page_name')->nullable();
            $table->string('role_name')->nullable();
            $table->string('severity')->default('medium');
            $table->string('status')->default('open');
            $table->text('description');
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banking_ui_release_issues');
        Schema::dropIfExists('banking_ui_release_checks');
    }
};
