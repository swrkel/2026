<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('banking_tester_issue_reports')) {
            Schema::create('banking_tester_issue_reports', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->string('module_key', 100)->index();
                $table->string('page_title')->nullable();
                $table->string('route_name')->nullable();
                $table->string('url')->nullable();
                $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium')->index();
                $table->enum('status', ['open', 'checking', 'fixed', 'retest', 'closed'])->default('open')->index();
                $table->text('summary');
                $table->longText('steps_to_reproduce')->nullable();
                $table->longText('expected_result')->nullable();
                $table->longText('actual_result')->nullable();
                $table->string('screenshot_reference')->nullable();
                $table->unsignedBigInteger('reported_by')->nullable()->index();
                $table->unsignedBigInteger('assigned_to')->nullable()->index();
                $table->timestamp('fixed_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('banking_tester_ui_check_results')) {
            Schema::create('banking_tester_ui_check_results', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->string('module_key', 100)->index();
                $table->string('check_key', 150)->index();
                $table->string('check_title');
                $table->enum('result', ['pending', 'pass', 'fail', 'blocked'])->default('pending')->index();
                $table->longText('notes')->nullable();
                $table->unsignedBigInteger('checked_by')->nullable()->index();
                $table->timestamp('checked_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('banking_tester_ui_check_results');
        Schema::dropIfExists('banking_tester_issue_reports');
    }
};
