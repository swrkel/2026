<?php

namespace Modules\ManagementReport\Services\Installation;

use Illuminate\Database\Schema\Blueprint;
use Modules\ManagementReport\Support\TenantConnection;

class TenantSchemaInstaller
{
    public function install(): void
    {
        $schema = TenantConnection::schema();

        if (!$schema->hasTable('mgmt_report_templates')) {
            $schema->create('mgmt_report_templates', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('store_id')->nullable()->index();
                $table->string('name', 150);
                $table->string('report_type', 80)->default('daily_management')->index();
                $table->json('section_keys')->nullable();
                $table->json('filter_defaults')->nullable();
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'name'], 'mgmt_tpl_business_name_unique');
                $table->index(['business_id', 'location_id', 'store_id'], 'mgmt_tpl_scope_idx');
            });
        }

        if (!$schema->hasTable('mgmt_report_runs')) {
            $schema->create('mgmt_report_runs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->uuid('uuid')->unique();
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('store_id')->nullable()->index();
                $table->unsignedBigInteger('shift_id')->nullable()->index();
                $table->string('report_type', 80)->default('daily_management')->index();
                $table->string('report_title', 190);
                $table->date('period_start')->index();
                $table->date('period_end')->index();
                $table->json('filter_payload')->nullable();
                $table->longText('snapshot_payload')->nullable();
                $table->string('status', 40)->default('generated')->index();
                $table->string('review_status', 40)->default('pending')->index();
                $table->unsignedBigInteger('generated_by')->nullable()->index();
                $table->timestamp('generated_at')->nullable()->index();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['business_id', 'period_start', 'period_end'], 'mgmt_runs_scope_period_idx');
            });
        }

        if (!$schema->hasTable('mgmt_report_run_sections')) {
            $schema->create('mgmt_report_run_sections', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('report_run_id')->index();
                $table->string('section_key', 80)->index();
                $table->string('section_label', 190);
                $table->unsignedInteger('sort_order')->default(0);
                $table->longText('section_payload')->nullable();
                $table->timestamps();
                $table->unique(['report_run_id', 'section_key'], 'mgmt_run_section_unique');
                $table->index(['report_run_id', 'sort_order'], 'mgmt_run_section_sort_idx');
            });
        }

        if (!$schema->hasTable('mgmt_report_shares')) {
            $schema->create('mgmt_report_shares', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('report_run_id')->index();
                $table->unsignedBigInteger('business_id')->index();
                $table->string('channel', 30)->index();
                $table->string('token', 64)->unique();
                $table->string('status', 40)->default('pending')->index();
                $table->string('provider', 120)->nullable();
                $table->text('message_body')->nullable();
                $table->longText('provider_response')->nullable();
                $table->text('failure_reason')->nullable();
                $table->unsignedInteger('view_count')->default(0);
                $table->timestamp('last_viewed_at')->nullable();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('revoked_by')->nullable();
                $table->timestamps();
                $table->index(['business_id', 'channel', 'status'], 'mgmt_shares_scope_idx');
            });
        }

        if (!$schema->hasTable('mgmt_report_share_recipients')) {
            $schema->create('mgmt_report_share_recipients', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('report_share_id')->index();
                $table->string('recipient', 190);
                $table->string('status', 40)->default('pending')->index();
                $table->string('provider_message_id', 190)->nullable()->index();
                $table->longText('provider_response')->nullable();
                $table->text('failure_reason')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->timestamps();
            });
        }

        if (!$schema->hasTable('mgmt_report_settings')) {
            $schema->create('mgmt_report_settings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('store_id')->nullable()->index();
                $table->string('setting_key', 120);
                $table->longText('setting_value')->nullable();
                $table->boolean('is_encrypted')->default(false);
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'location_id', 'store_id', 'setting_key'], 'mgmt_setting_scope_unique');
            });
        }

        if (!$schema->hasTable('mgmt_report_review_statuses')) {
            $schema->create('mgmt_report_review_statuses', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('report_run_id')->index();
                $table->unsignedBigInteger('business_id')->index();
                $table->string('review_status', 40)->index();
                $table->text('review_notes')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
                $table->index(['business_id', 'review_status'], 'mgmt_review_scope_idx');
            });
        }
    }

    public function uninstall(): void
    {
        $schema = TenantConnection::schema();
        $schema->dropIfExists('mgmt_report_review_statuses');
        $schema->dropIfExists('mgmt_report_settings');
        $schema->dropIfExists('mgmt_report_share_recipients');
        $schema->dropIfExists('mgmt_report_shares');
        $schema->dropIfExists('mgmt_report_run_sections');
        $schema->dropIfExists('mgmt_report_runs');
        $schema->dropIfExists('mgmt_report_templates');
    }
}
