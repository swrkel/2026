<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('created_by_id')->nullable()->index('created_by_id');
            $table->enum('contact_type', ['customer', 'supplier'])->default('customer');
            $table->unsignedInteger('contact_id')->nullable()->index('contact_id');
            $table->unsignedBigInteger('group_id')->nullable()->index('group_id');
            $table->unsignedInteger('location_id')->nullable()->index('loans_branch_id_index');
            $table->unsignedBigInteger('loan_application_id')->nullable()->index('loan_application_id');
            $table->unsignedBigInteger('currency_id')->index('currency_id');
            $table->unsignedBigInteger('loan_product_id')->index('loan_product_id');
            $table->integer('variation_id')->nullable()->index('variation_id');
            $table->unsignedBigInteger('loan_transaction_processing_strategy_id')->index('loan_transaction_processing_strategy_id');
            $table->unsignedBigInteger('loan_purpose_id')->index('loan_purpose_id');
            $table->unsignedBigInteger('loan_officer_id')->index('loan_officer_id');
            $table->unsignedBigInteger('linked_savings_id')->nullable()->index('linked_savings_id');
            $table->unsignedBigInteger('loan_disbursement_channel_id')->nullable()->index('loan_disbursement_channel_id');
            $table->date('submitted_on_date')->nullable();
            $table->unsignedBigInteger('submitted_by_user_id')->nullable()->index('submitted_by_user_id');
            $table->date('approved_on_date')->nullable();
            $table->unsignedBigInteger('approved_by_user_id')->nullable()->index('approved_by_user_id');
            $table->text('approved_notes')->nullable();
            $table->date('expected_disbursement_date')->nullable();
            $table->date('expected_first_payment_date')->nullable();
            $table->date('first_payment_date')->nullable();
            $table->date('expected_maturity_date')->nullable();
            $table->date('disbursed_on_date')->nullable();
            $table->unsignedBigInteger('disbursed_by_user_id')->nullable()->index('disbursed_by_user_id');
            $table->text('disbursed_notes')->nullable();
            $table->date('rejected_on_date')->nullable();
            $table->unsignedBigInteger('rejected_by_user_id')->nullable()->index('rejected_by_user_id');
            $table->text('rejected_notes')->nullable();
            $table->date('written_off_on_date')->nullable();
            $table->unsignedBigInteger('written_off_by_user_id')->nullable()->index('written_off_by_user_id');
            $table->text('written_off_notes')->nullable();
            $table->date('closed_on_date')->nullable();
            $table->unsignedBigInteger('closed_by_user_id')->nullable()->index('closed_by_user_id');
            $table->text('closed_notes')->nullable();
            $table->date('rescheduled_on_date')->nullable();
            $table->unsignedBigInteger('rescheduled_by_user_id')->nullable()->index('rescheduled_by_user_id');
            $table->text('rescheduled_notes')->nullable();
            $table->date('withdrawn_on_date')->nullable();
            $table->unsignedBigInteger('withdrawn_by_user_id')->nullable()->index('withdrawn_by_user_id');
            $table->text('withdrawn_notes')->nullable();
            $table->string('external_id')->nullable()->index('external_id');
            $table->string('account_number')->nullable()->unique();
            $table->decimal('principal', 65, 6);
            $table->decimal('applied_amount', 65, 4)->nullable();
            $table->decimal('top_up_amount', 65, 6)->nullable();
            $table->decimal('approved_amount', 65, 6)->nullable();
            $table->decimal('interest_rate', 65, 6);
            $table->integer('decimals')->nullable();
            $table->integer('instalment_multiple_of')->nullable()->default(1);
            $table->integer('loan_term');
            $table->integer('repayment_frequency');
            $table->enum('repayment_frequency_type', ['days', 'weeks', 'months', 'years']);
            $table->enum('interest_rate_type', ['day', 'week', 'month', 'year']);
            $table->tinyInteger('enable_balloon_payments')->default(0);
            $table->tinyInteger('allow_schedule_adjustments')->default(0);
            $table->integer('grace_on_principal_paid')->default(0);
            $table->integer('grace_on_interest_paid')->default(0);
            $table->integer('grace_on_interest_charged')->default(0);
            $table->tinyInteger('allow_custom_grace_period')->default(0);
            $table->tinyInteger('allow_topup')->default(0);
            $table->enum('interest_methodology', ['flat', 'declining_balance']);
            $table->tinyInteger('interest_recalculation')->default(0);
            $table->enum('amortization_method', ['equal_installments', 'equal_principal_payments'])->nullable();
            $table->enum('interest_calculation_period_type', ['daily', 'same'])->nullable();
            $table->enum('days_in_year', ['actual', '360', '365', '364'])->nullable()->default('actual');
            $table->enum('days_in_month', ['actual', '30', '31'])->nullable()->default('actual');
            $table->tinyInteger('include_in_loan_cycle')->default(0);
            $table->tinyInteger('lock_guarantee_funds')->default(0);
            $table->tinyInteger('auto_allocate_overpayments')->default(0);
            $table->tinyInteger('allow_additional_charges')->default(0);
            $table->tinyInteger('auto_disburse')->default(0);
            $table->enum('status', ['pending', 'approved', 'active', 'withdrawn', 'rejected', 'closed', 'rescheduled', 'written_off', 'overpaid', 'submitted', 'fully_paid'])->default('submitted');
            $table->boolean('is_signed')->nullable()->default(false);
            $table->decimal('disbursement_charges', 65, 6)->nullable();
            $table->decimal('principal_disbursed_derived', 65, 6)->default(0);
            $table->decimal('principal_repaid_derived', 65, 6)->default(0);
            $table->decimal('principal_written_off_derived', 65, 6)->default(0);
            $table->decimal('principal_outstanding_derived', 65, 6)->default(0);
            $table->decimal('interest_disbursed_derived', 65, 6)->default(0);
            $table->decimal('interest_repaid_derived', 65, 6)->default(0);
            $table->decimal('interest_written_off_derived', 65, 6)->default(0);
            $table->decimal('interest_waived_derived', 65, 6)->default(0);
            $table->decimal('interest_outstanding_derived', 65, 6)->default(0);
            $table->decimal('fees_disbursed_derived', 65, 6)->default(0);
            $table->decimal('fees_repaid_derived', 65, 6)->default(0);
            $table->decimal('fees_written_off_derived', 65, 6)->default(0);
            $table->decimal('fees_waived_derived', 65, 6)->default(0);
            $table->decimal('fees_outstanding_derived', 65, 6)->default(0);
            $table->decimal('penalties_disbursed_derived', 65, 6)->default(0);
            $table->decimal('penalties_repaid_derived', 65, 6)->default(0);
            $table->decimal('penalties_written_off_derived', 65, 6)->default(0);
            $table->decimal('penalties_waived_derived', 65, 6)->default(0);
            $table->decimal('penalties_outstanding_derived', 65, 6)->default(0);
            $table->decimal('total_disbursed_derived', 65, 6)->default(0);
            $table->decimal('total_repaid_derived', 65, 6)->default(0);
            $table->decimal('total_written_off_derived', 65, 6)->default(0);
            $table->decimal('total_waived_derived', 65, 6)->default(0);
            $table->decimal('total_outstanding_derived', 65, 6)->default(0);
            $table->enum('accounting_rule', ['none', 'cash', 'accrual_periodic', 'accrual_upfront'])->nullable()->default('none');
            $table->unsignedBigInteger('fund_source_chart_of_account_id')->nullable()->index('fund_source_chart_of_account_id');
            $table->unsignedBigInteger('loan_portfolio_chart_of_account_id')->nullable()->index('loan_portfolio_chart_of_account_id');
            $table->unsignedBigInteger('suspended_income_chart_of_account_id')->nullable()->index('suspended_income_chart_of_account_id');
            $table->unsignedBigInteger('interest_receivable_chart_of_account_id')->nullable()->index('interest_receivable_chart_of_account_id');
            $table->unsignedBigInteger('fees_receivable_chart_of_account_id')->nullable()->index('fees_receivable_chart_of_account_id');
            $table->unsignedBigInteger('penalties_receivable_chart_of_account_id')->nullable()->index('penalties_receivable_chart_of_account_id');
            $table->unsignedBigInteger('transfer_in_suspense_chart_of_account_id')->nullable()->index('transfer_in_suspense_chart_of_account_id');
            $table->unsignedBigInteger('income_from_interest_chart_of_account_id')->nullable()->index('income_from_interest_chart_of_account_id');
            $table->unsignedBigInteger('income_from_penalties_chart_of_account_id')->nullable()->index('income_from_penalties_chart_of_account_id');
            $table->unsignedBigInteger('income_from_fees_chart_of_account_id')->nullable()->index('income_from_fees_chart_of_account_id');
            $table->unsignedBigInteger('income_from_recovery_chart_of_account_id')->nullable()->index('income_from_recovery_chart_of_account_id');
            $table->unsignedBigInteger('losses_written_off_chart_of_account_id')->nullable()->index('losses_written_off_chart_of_account_id');
            $table->unsignedBigInteger('interest_written_off_chart_of_account_id')->nullable()->index('interest_written_off_chart_of_account_id');
            $table->unsignedBigInteger('overpayments_chart_of_account_id')->nullable()->index('overpayments_chart_of_account_id');
            $table->timestamps();

            $table->index(['contact_id']);
            $table->unique(['external_id']);
            $table->index(['loan_officer_id']);
            $table->index(['loan_product_id']);
            $table->index(['location_id']);
            $table->index(['location_id'], 'location_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('loans');
    }
};
