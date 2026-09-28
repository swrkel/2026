<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLawCompletionTables extends Migration
{
    public function up()
    {
        Schema::create('law_court_filings', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('business_id')->index();
            $t->unsignedBigInteger('matter_id')->index();
            $t->unsignedBigInteger('court_id')->nullable()->index();
            $t->string('filing_no', 80)->index();
            $t->string('filing_type', 100)->index();
            $t->string('title');
            $t->date('filed_on')->nullable()->index();
            $t->unsignedBigInteger('filed_by_user_id')->nullable()->index();
            $t->string('reference_no', 120)->nullable()->index();
            $t->decimal('fee_amount', 22, 4)->default(0);
            $t->string('status', 30)->default('draft')->index();
            $t->dateTime('response_due_at')->nullable()->index();
            $t->text('notes')->nullable();
            $t->unsignedBigInteger('created_by')->nullable()->index();
            $t->timestamps();
            $t->unique(['business_id', 'matter_id', 'filing_no'], 'law_court_filings_unique');
        });

        Schema::create('law_settlements', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('business_id')->index();
            $t->unsignedBigInteger('matter_id')->index();
            $t->unsignedBigInteger('client_id')->nullable()->index();
            $t->string('settlement_no', 80)->index();
            $t->string('settlement_type', 60)->default('negotiation')->index();
            $t->decimal('offer_amount', 22, 4)->default(0);
            $t->decimal('agreed_amount', 22, 4)->default(0);
            $t->string('status', 30)->default('draft')->index();
            $t->date('offered_on')->nullable()->index();
            $t->date('accepted_on')->nullable()->index();
            $t->date('completed_on')->nullable()->index();
            $t->longText('terms')->nullable();
            $t->boolean('confidential')->default(false)->index();
            $t->unsignedBigInteger('created_by')->nullable()->index();
            $t->timestamps();
            $t->unique(['business_id', 'settlement_no']);
        });

        Schema::create('law_mediation_sessions', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('business_id')->index();
            $t->unsignedBigInteger('settlement_id')->nullable()->index();
            $t->unsignedBigInteger('matter_id')->index();
            $t->string('mediator')->nullable();
            $t->string('venue')->nullable();
            $t->dateTime('session_at')->index();
            $t->string('status', 30)->default('scheduled')->index();
            $t->text('outcome')->nullable();
            $t->dateTime('next_session_at')->nullable()->index();
            $t->text('notes')->nullable();
            $t->unsignedBigInteger('created_by')->nullable()->index();
            $t->timestamps();
        });

        Schema::create('law_research_items', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('business_id')->index();
            $t->unsignedBigInteger('matter_id')->nullable()->index();
            $t->unsignedBigInteger('practice_area_id')->nullable()->index();
            $t->string('title');
            $t->string('citation', 255)->nullable()->index();
            $t->string('source_type', 60)->default('case')->index();
            $t->string('source_url', 1000)->nullable();
            $t->string('court')->nullable();
            $t->string('jurisdiction', 120)->nullable()->index();
            $t->date('decision_date')->nullable()->index();
            $t->longText('summary')->nullable();
            $t->longText('key_points')->nullable();
            $t->text('keywords')->nullable();
            $t->boolean('confidential')->default(false)->index();
            $t->unsignedBigInteger('created_by')->nullable()->index();
            $t->timestamps();
        });

        Schema::create('law_fee_estimates', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('business_id')->index();
            $t->unsignedBigInteger('client_id')->index();
            $t->unsignedBigInteger('matter_id')->nullable()->index();
            $t->string('estimate_no', 80)->index();
            $t->date('estimate_date')->index();
            $t->date('valid_until')->nullable()->index();
            $t->string('status', 30)->default('draft')->index();
            $t->decimal('subtotal', 22, 4)->default(0);
            $t->decimal('tax_amount', 22, 4)->default(0);
            $t->decimal('discount_amount', 22, 4)->default(0);
            $t->decimal('total', 22, 4)->default(0);
            $t->text('notes')->nullable();
            $t->unsignedBigInteger('invoice_id')->nullable()->index();
            $t->dateTime('accepted_at')->nullable();
            $t->unsignedBigInteger('created_by')->nullable()->index();
            $t->timestamps();
            $t->unique(['business_id', 'estimate_no']);
        });

        Schema::create('law_fee_estimate_lines', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('business_id')->index();
            $t->unsignedBigInteger('estimate_id')->index();
            $t->text('description');
            $t->decimal('qty', 18, 4)->default(1);
            $t->decimal('unit_price', 22, 4)->default(0);
            $t->decimal('tax_rate', 12, 4)->default(0);
            $t->decimal('line_total', 22, 4)->default(0);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('law_client_advances', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('business_id')->index();
            $t->unsignedBigInteger('client_id')->nullable()->index();
            $t->unsignedBigInteger('matter_id')->nullable()->index();
            $t->string('advance_type', 30)->default('client')->index();
            $t->unsignedBigInteger('recipient_user_id')->nullable()->index();
            $t->string('advance_no', 80)->index();
            $t->date('received_on')->index();
            $t->decimal('amount', 22, 4);
            $t->decimal('allocated_amount', 22, 4)->default(0);
            $t->decimal('balance', 22, 4)->default(0);
            $t->string('method', 50);
            $t->string('reference', 120)->nullable();
            $t->text('notes')->nullable();
            $t->string('status', 30)->default('active')->index();
            $t->string('finance_sync_status', 30)->default('pending')->index();
            $t->string('finance_reference', 120)->nullable();
            $t->unsignedBigInteger('created_by')->nullable()->index();
            $t->timestamps();
            $t->unique(['business_id', 'advance_no']);
        });

        Schema::create('law_advance_allocations', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('business_id')->index();
            $t->unsignedBigInteger('advance_id')->index();
            $t->unsignedBigInteger('invoice_id')->index();
            $t->date('allocated_on')->index();
            $t->decimal('amount', 22, 4);
            $t->text('notes')->nullable();
            $t->string('finance_sync_status', 30)->default('pending')->index();
            $t->string('finance_reference', 120)->nullable();
            $t->unsignedBigInteger('created_by')->nullable()->index();
            $t->timestamps();
        });

        Schema::create('law_document_approvals', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('business_id')->index();
            $t->unsignedBigInteger('document_id')->index();
            $t->unsignedBigInteger('matter_id')->nullable()->index();
            $t->unsignedBigInteger('requested_by')->nullable()->index();
            $t->unsignedBigInteger('approver_user_id')->index();
            $t->unsignedInteger('version_no')->nullable();
            $t->string('status', 30)->default('pending')->index();
            $t->dateTime('requested_at')->index();
            $t->dateTime('responded_at')->nullable();
            $t->text('comments')->nullable();
            $t->timestamps();
        });

        Schema::create('law_esign_requests', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('business_id')->index();
            $t->unsignedBigInteger('document_id')->index();
            $t->unsignedBigInteger('matter_id')->nullable()->index();
            $t->unsignedBigInteger('client_id')->nullable()->index();
            $t->string('recipient_name');
            $t->string('recipient_email')->index();
            $t->string('token_hash', 64)->index();
            $t->unsignedInteger('version_no')->nullable();
            $t->string('status', 30)->default('pending')->index();
            $t->dateTime('requested_at')->index();
            $t->dateTime('expires_at')->nullable()->index();
            $t->dateTime('viewed_at')->nullable();
            $t->dateTime('signed_at')->nullable();
            $t->dateTime('declined_at')->nullable();
            $t->string('signature_name')->nullable();
            $t->string('signature_ip', 64)->nullable();
            $t->text('signature_user_agent')->nullable();
            $t->unsignedBigInteger('created_by')->nullable()->index();
            $t->timestamps();
        });

        Schema::create('law_notification_rules', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('business_id')->index();
            $t->string('name');
            $t->string('event_key', 80)->index();
            $t->string('channel', 30)->default('in_app')->index();
            $t->integer('days_before')->default(0);
            $t->string('recipient_type', 40)->default('assigned_user');
            $t->unsignedBigInteger('user_id')->nullable()->index();
            $t->boolean('active')->default(true)->index();
            $t->longText('conditions_json')->nullable();
            $t->unsignedBigInteger('created_by')->nullable()->index();
            $t->timestamps();
        });

        Schema::create('law_lawyer_targets', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('business_id')->index();
            $t->unsignedBigInteger('user_id')->index();
            $t->string('period_type', 20)->default('monthly')->index();
            $t->date('period_start')->index();
            $t->date('period_end')->index();
            $t->decimal('target_hours', 18, 2)->default(0);
            $t->decimal('target_billing', 22, 4)->default(0);
            $t->decimal('target_collections', 22, 4)->default(0);
            $t->unsignedInteger('target_new_matters')->default(0);
            $t->decimal('cost_rate', 22, 4)->default(0);
            $t->text('notes')->nullable();
            $t->unsignedBigInteger('created_by')->nullable()->index();
            $t->timestamps();
            $t->unique(['business_id', 'user_id', 'period_start', 'period_end'], 'law_lawyer_targets_unique');
        });

        Schema::create('law_matter_closures', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('business_id')->index();
            $t->unsignedBigInteger('matter_id')->index();
            $t->date('closure_date')->index();
            $t->string('closure_reason', 120);
            $t->text('outcome')->nullable();
            $t->decimal('final_fee_amount', 22, 4)->default(0);
            $t->boolean('client_notified')->default(false);
            $t->boolean('documents_archived')->default(false);
            $t->boolean('trust_cleared')->default(false);
            $t->boolean('billing_cleared')->default(false);
            $t->unsignedBigInteger('closed_by')->nullable()->index();
            $t->dateTime('reopened_at')->nullable();
            $t->unsignedBigInteger('reopened_by')->nullable()->index();
            $t->text('reopen_reason')->nullable();
            $t->unsignedBigInteger('created_by')->nullable()->index();
            $t->timestamps();
            $t->unique(['business_id', 'matter_id']);
        });

        Schema::create('law_portal_audit_logs', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('business_id')->index();
            $t->unsignedBigInteger('client_id')->index();
            $t->unsignedBigInteger('portal_access_id')->nullable()->index();
            $t->unsignedBigInteger('matter_id')->nullable()->index();
            $t->string('action', 100)->index();
            $t->string('ip_address', 64)->nullable();
            $t->text('user_agent')->nullable();
            $t->longText('metadata_json')->nullable();
            $t->dateTime('created_at')->index();
        });
    }

    public function down()
    {
        foreach ([
            'law_portal_audit_logs', 'law_matter_closures', 'law_lawyer_targets', 'law_notification_rules',
            'law_esign_requests', 'law_document_approvals', 'law_advance_allocations', 'law_client_advances',
            'law_fee_estimate_lines', 'law_fee_estimates', 'law_research_items', 'law_mediation_sessions',
            'law_settlements', 'law_court_filings'
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
}
