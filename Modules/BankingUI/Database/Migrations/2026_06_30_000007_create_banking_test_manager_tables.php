<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBankingTestManagerTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('banking_test_module_statuses')) {
            Schema::create('banking_test_module_statuses', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->string('module_key', 100)->index();
                $table->string('module_name', 150);
                $table->string('status', 60)->default('Ready for UI Testing')->index();
                $table->unsignedTinyInteger('development_percent')->default(0);
                $table->unsignedTinyInteger('ui_tested_percent')->default(0);
                $table->unsignedTinyInteger('uat_percent')->default(0);
                $table->unsignedTinyInteger('production_ready_percent')->default(0);
                $table->unsignedInteger('total_pages')->default(0);
                $table->unsignedInteger('tested_pages')->default(0);
                $table->unsignedInteger('total_reports')->default(0);
                $table->unsignedInteger('tested_reports')->default(0);
                $table->unsignedInteger('total_routes')->default(0);
                $table->unsignedInteger('tested_routes')->default(0);
                $table->unsignedInteger('total_permissions')->default(0);
                $table->unsignedInteger('tested_permissions')->default(0);
                $table->text('notes')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->unsignedInteger('updated_by')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'module_key'], 'bkg_test_module_business_unique');
            });
        }

        if (!Schema::hasTable('banking_test_issues')) {
            Schema::create('banking_test_issues', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->string('issue_no', 50)->nullable()->index();
                $table->string('module_key', 100)->index();
                $table->string('page_name', 150)->nullable();
                $table->string('route_name', 150)->nullable();
                $table->string('title', 191);
                $table->string('priority', 30)->default('Medium')->index();
                $table->string('status', 50)->default('Open')->index();
                $table->text('steps_to_reproduce')->nullable();
                $table->text('expected_result')->nullable();
                $table->text('actual_result')->nullable();
                $table->text('developer_notes')->nullable();
                $table->text('resolution_notes')->nullable();
                $table->string('screenshot_path')->nullable();
                $table->unsignedInteger('assigned_to')->nullable()->index();
                $table->unsignedInteger('reported_by')->nullable()->index();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('banking_test_checklist_results')) {
            Schema::create('banking_test_checklist_results', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->string('module_key', 100)->index();
                $table->string('check_type', 60)->index();
                $table->string('check_name', 191);
                $table->string('result', 30)->default('Pending')->index();
                $table->text('notes')->nullable();
                $table->unsignedInteger('checked_by')->nullable();
                $table->timestamp('checked_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('banking_test_checklist_results');
        Schema::dropIfExists('banking_test_issues');
        Schema::dropIfExists('banking_test_module_statuses');
    }
}
