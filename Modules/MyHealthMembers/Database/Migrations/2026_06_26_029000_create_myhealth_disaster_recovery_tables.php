<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('myhealth_backup_records')) {
            Schema::create('myhealth_backup_records', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->string('backup_no', 50)->unique();
                $table->string('backup_type', 30)->default('manual')->index();
                $table->string('backup_scope', 50)->default('database_and_files');
                $table->string('status', 30)->default('queued')->index();
                $table->string('storage_disk', 50)->nullable();
                $table->string('storage_path')->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('myhealth_restore_records')) {
            Schema::create('myhealth_restore_records', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->string('restore_no', 50)->unique();
                $table->unsignedBigInteger('backup_record_id')->nullable()->index();
                $table->string('restore_scope', 50)->default('preview');
                $table->string('status', 30)->default('requested')->index();
                $table->timestamp('validated_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->unsignedBigInteger('requested_by')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('myhealth_system_health_checks')) {
            Schema::create('myhealth_system_health_checks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->string('check_key', 80)->index();
                $table->string('check_name');
                $table->string('status', 30)->default('unknown')->index();
                $table->string('severity', 30)->default('normal')->index();
                $table->timestamp('checked_at')->nullable()->index();
                $table->text('message')->nullable();
                $table->json('details')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('myhealth_recovery_tests')) {
            Schema::create('myhealth_recovery_tests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->string('test_no', 50)->unique();
                $table->string('test_type', 50)->index();
                $table->string('status', 30)->default('planned')->index();
                $table->timestamp('tested_at')->nullable();
                $table->unsignedBigInteger('tested_by')->nullable();
                $table->unsignedInteger('duration_seconds')->nullable();
                $table->text('result_summary')->nullable();
                $table->text('issues_found')->nullable();
                $table->text('recommendations')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('myhealth_recovery_tests');
        Schema::dropIfExists('myhealth_system_health_checks');
        Schema::dropIfExists('myhealth_restore_records');
        Schema::dropIfExists('myhealth_backup_records');
    }
};
