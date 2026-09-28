<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('mn_members')) {
            Schema::create('mn_members', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->string('member_code', 50)->nullable();
                $table->string('first_name', 100);
                $table->string('last_name', 100)->nullable();
                $table->string('full_name', 255)->nullable();
                $table->string('full_name_second_language', 255)->nullable();
                $table->string('mobile', 30)->nullable();
                $table->string('email')->nullable();
                $table->string('nic', 50)->nullable();
                $table->date('date_of_birth')->nullable();
                $table->date('joined_on')->nullable();
                $table->text('address')->nullable();
                $table->text('note')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['business_id', 'member_code'], 'mn_members_business_code_uq');
            });
        }

        if (!Schema::hasTable('mn_plans')) {
            Schema::create('mn_plans', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->index();
                $table->string('name', 150);
                $table->unsignedInteger('duration_days')->default(30);
                $table->decimal('price', 22, 4)->default(0);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['business_id', 'name'], 'mn_plans_business_name_uq');
            });
        }

        if (!Schema::hasTable('mn_payments')) {
            Schema::create('mn_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('member_id')->index();
                $table->unsignedBigInteger('plan_id')->nullable()->index();
                $table->date('payment_date');
                $table->string('payment_ref_no', 100)->nullable();
                $table->decimal('amount', 22, 4)->default(0);
                $table->string('payment_method', 50)->nullable();
                $table->text('note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('mn_linked_businesses')) {
            Schema::create('mn_linked_businesses', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->index();
                $table->unsignedInteger('linked_business_id')->index();
                $table->unsignedInteger('outlet_business_id')->nullable()->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->string('name', 190)->nullable();
                $table->text('note')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['business_id', 'linked_business_id'], 'mn_linked_businesses_uq');
            });
        }

        if (!Schema::hasTable('mn_point_rules')) {
            Schema::create('mn_point_rules', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->index();
                $table->unsignedInteger('outlet_business_id')->nullable()->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('category_id')->nullable()->index();
                $table->decimal('amount_step', 22, 4)->default(1);
                $table->decimal('points_per_amount', 22, 4)->default(0);
                $table->decimal('max_points_per_invoice', 22, 4)->nullable();
                $table->text('note')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('mn_point_transactions')) {
            Schema::create('mn_point_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->index();
                $table->unsignedBigInteger('member_id')->nullable()->index();
                $table->unsignedBigInteger('member_business_map_id')->nullable()->index();
                $table->unsignedInteger('outlet_business_id')->nullable()->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('category_id')->nullable()->index();
                $table->dateTime('transaction_date')->nullable()->index();
                $table->string('type', 30)->index();
                $table->decimal('points', 22, 4)->default(0);
                $table->decimal('purchase_amount', 22, 4)->default(0);
                $table->string('reference_type', 100)->nullable()->index();
                $table->unsignedBigInteger('reference_id')->nullable()->index();
                $table->text('note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('mn_share_holdings')) {
            Schema::create('mn_share_holdings', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->index();
                $table->unsignedBigInteger('member_id')->index();
                $table->decimal('shares', 22, 4)->default(0);
                $table->decimal('share_value', 22, 4)->default(0);
                $table->text('note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['business_id', 'member_id'], 'mn_share_holdings_business_member_uq');
            });
        }

        if (!Schema::hasTable('mn_dividend_batches')) {
            Schema::create('mn_dividend_batches', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->index();
                $table->date('dividend_date')->index();
                $table->decimal('total_dividend_amount', 22, 4)->default(0);
                $table->decimal('dividend_per_share', 22, 6)->default(0);
                $table->text('note')->nullable();
                $table->boolean('is_posted')->default(false)->index();
                $table->dateTime('posted_at')->nullable();
                $table->unsignedBigInteger('posted_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('mn_dividend_payments')) {
            Schema::create('mn_dividend_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->index();
                $table->unsignedBigInteger('batch_id')->index();
                $table->unsignedBigInteger('member_id')->nullable()->index();
                $table->unsignedBigInteger('central_member_id')->nullable()->index();
                $table->unsignedBigInteger('member_business_map_id')->nullable()->index();
                $table->decimal('shares', 22, 4)->default(0);
                $table->decimal('amount', 22, 4)->default(0);
                $table->boolean('is_paid')->default(false)->index();
                $table->dateTime('paid_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('mn_identity_cards')) {
            Schema::create('mn_identity_cards', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->index();
                $table->unsignedBigInteger('member_id')->index();
                $table->string('card_no', 100)->unique();
                $table->text('qr_payload')->nullable();
                $table->date('issued_on')->nullable();
                $table->date('expires_on')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->dateTime('blocked_at')->nullable();
                $table->unsignedBigInteger('blocked_by')->nullable();
                $table->text('blocked_reason')->nullable();
                $table->dateTime('last_scanned_at')->nullable();
                $table->unsignedBigInteger('last_scanned_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('mn_customer_maps')) {
            Schema::create('mn_customer_maps', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->index();
                $table->unsignedBigInteger('member_id')->index();
                $table->unsignedInteger('linked_business_id')->index();
                $table->unsignedBigInteger('linked_customer_id')->nullable()->index();
                $table->json('member_snapshot')->nullable();
                $table->boolean('is_synced')->default(false)->index();
                $table->dateTime('last_synced_at')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['business_id', 'member_id', 'linked_business_id'], 'mn_customer_maps_uq');
            });
        }

        if (!Schema::hasTable('mn_central_members')) {
            Schema::create('mn_central_members', function (Blueprint $table) {
                $table->id();
                $table->string('central_member_code', 80)->nullable()->unique();
                $table->string('first_name', 100);
                $table->string('last_name', 100)->nullable();
                $table->string('mobile', 30)->nullable()->index();
                $table->string('email')->nullable()->index();
                $table->string('nic', 50)->nullable()->index();
                $table->date('date_of_birth')->nullable();
                $table->text('address')->nullable();
                $table->text('note')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('mn_member_business_maps')) {
            Schema::create('mn_member_business_maps', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('central_member_id')->index();
                $table->unsignedInteger('business_id')->index();
                $table->unsignedBigInteger('local_customer_id')->nullable()->index();
                $table->unsignedBigInteger('local_member_id')->nullable()->index();
                $table->boolean('points_enabled')->default(true);
                $table->boolean('dividend_enabled')->default(true);
                $table->boolean('is_active')->default(true)->index();
                $table->dateTime('last_activity_at')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['central_member_id', 'business_id'], 'mn_member_business_maps_uq');
            });
        }

        if (!Schema::hasTable('mn_business_customer_histories')) {
            Schema::create('mn_business_customer_histories', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->index();
                $table->unsignedBigInteger('member_business_map_id')->index();
                $table->dateTime('transaction_date')->nullable()->index();
                $table->string('transaction_type', 80)->nullable()->index();
                $table->decimal('debit', 22, 4)->default(0);
                $table->decimal('credit', 22, 4)->default(0);
                $table->decimal('balance', 22, 4)->default(0);
                $table->string('reference_type', 100)->nullable()->index();
                $table->unsignedBigInteger('reference_id')->nullable()->index();
                $table->text('note')->nullable();
                $table->json('meta')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('mn_duplicate_candidates')) {
            Schema::create('mn_duplicate_candidates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('primary_central_member_id')->index();
                $table->unsignedBigInteger('duplicate_central_member_id')->index();
                $table->json('match_fields')->nullable();
                $table->decimal('confidence_score', 8, 4)->default(0);
                $table->boolean('is_resolved')->default(false)->index();
                $table->dateTime('resolved_at')->nullable();
                $table->unsignedBigInteger('resolved_by')->nullable();
                $table->text('resolution_note')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['primary_central_member_id', 'duplicate_central_member_id'], 'mn_duplicate_candidates_uq');
            });
        }

        if (!Schema::hasTable('mn_outlet_transaction_queue')) {
            Schema::create('mn_outlet_transaction_queue', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->index();
                $table->unsignedInteger('outlet_business_id')->nullable()->index();
                $table->unsignedBigInteger('member_business_map_id')->nullable()->index();
                $table->unsignedBigInteger('central_member_id')->nullable()->index();
                $table->unsignedBigInteger('member_id')->nullable()->index();
                $table->dateTime('transaction_date')->nullable()->index();
                $table->decimal('purchase_amount', 22, 4)->default(0);
                $table->decimal('earn_points', 22, 4)->default(0);
                $table->decimal('redeem_points', 22, 4)->default(0);
                $table->string('reference_type', 100)->nullable()->index();
                $table->unsignedBigInteger('reference_id')->nullable()->index();
                $table->json('payload')->nullable();
                $table->boolean('is_processed')->default(false)->index();
                $table->dateTime('processed_at')->nullable();
                $table->unsignedBigInteger('processed_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('mn_merge_requests')) {
            Schema::create('mn_merge_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('primary_central_member_id')->index();
                $table->unsignedBigInteger('duplicate_central_member_id')->index();
                $table->text('reason')->nullable();
                $table->json('merge_payload')->nullable();
                $table->boolean('is_approved')->default(false)->index();
                $table->dateTime('approved_at')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->dateTime('processed_at')->nullable();
                $table->unsignedBigInteger('processed_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('mn_dividend_payouts')) {
            Schema::create('mn_dividend_payouts', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->index();
                $table->unsignedBigInteger('dividend_payment_id')->index();
                $table->unsignedBigInteger('member_id')->nullable()->index();
                $table->unsignedBigInteger('central_member_id')->nullable()->index();
                $table->unsignedBigInteger('member_business_map_id')->nullable()->index();
                $table->decimal('amount', 22, 4)->default(0);
                $table->string('payment_method', 80)->nullable();
                $table->string('payment_ref_no', 150)->nullable();
                $table->dateTime('paid_at')->nullable()->index();
                $table->text('note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->boolean('is_reversed')->default(false)->index();
                $table->dateTime('reversed_at')->nullable();
                $table->unsignedBigInteger('reversed_by')->nullable();
                $table->text('reversal_note')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('mn_audit_logs')) {
            Schema::create('mn_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('action', 120)->index();
                $table->string('entity_type', 150)->nullable()->index();
                $table->unsignedBigInteger('entity_id')->nullable()->index();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address', 64)->nullable();
                $table->text('user_agent')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('mn_approval_requests')) {
            Schema::create('mn_approval_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->index();
                $table->string('request_type', 100)->index();
                $table->string('entity_type', 150)->nullable()->index();
                $table->unsignedBigInteger('entity_id')->nullable()->index();
                $table->json('payload')->nullable();
                $table->string('status', 30)->default('pending')->index();
                $table->unsignedBigInteger('requested_by')->nullable();
                $table->text('note')->nullable();
                $table->dateTime('approved_at')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->text('approval_note')->nullable();
                $table->dateTime('rejected_at')->nullable();
                $table->unsignedBigInteger('rejected_by')->nullable();
                $table->text('rejection_note')->nullable();
                $table->dateTime('processed_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('mn_business_access_rules')) {
            Schema::create('mn_business_access_rules', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->unique();
                $table->boolean('can_view_central_profile')->default(true);
                $table->boolean('can_view_other_business_history')->default(false);
                $table->boolean('can_redeem_cross_business_points')->default(true);
                $table->boolean('can_issue_card')->default(true);
                $table->boolean('is_active')->default(true);
                $table->text('note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('mn_error_logs')) {
            Schema::create('mn_error_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('error_class', 255)->nullable()->index();
                $table->text('message');
                $table->text('file')->nullable();
                $table->unsignedInteger('line')->nullable();
                $table->json('context')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('mn_import_batches')) {
            Schema::create('mn_import_batches', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->index();
                $table->string('import_type', 100)->nullable()->index();
                $table->string('file_name')->nullable();
                $table->string('status', 30)->default('pending')->index();
                $table->unsignedInteger('total_rows')->default(0);
                $table->unsignedInteger('success_rows')->default(0);
                $table->unsignedInteger('failed_rows')->default(0);
                $table->json('summary')->nullable();
                $table->dateTime('started_at')->nullable();
                $table->dateTime('finished_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        // Repair migration is intentionally non-destructive. Existing membership
        // data must never be dropped by rolling back a schema repair package.
    }
};
