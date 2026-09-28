<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinanceBankReconciliationTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('finance_bank_reconciliations')) {
            Schema::create('finance_bank_reconciliations', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id');
                $table->unsignedBigInteger('location_id')->nullable();
                $table->unsignedBigInteger('account_id');
                $table->string('reconciliation_no', 50);
                $table->date('statement_date');
                $table->decimal('statement_ending_balance', 22, 4)->default(0);
                $table->decimal('book_ending_balance', 22, 4)->default(0);
                $table->decimal('outstanding_deposits', 22, 4)->default(0);
                $table->decimal('outstanding_payments', 22, 4)->default(0);
                $table->decimal('adjusted_bank_balance', 22, 4)->default(0);
                $table->decimal('difference', 22, 4)->default(0);
                $table->string('status', 20)->default('draft');
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by');
                $table->unsignedBigInteger('reconciled_by')->nullable();
                $table->dateTime('reconciled_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['business_id', 'reconciliation_no'], 'finance_bank_rec_business_no_uq');
                $table->index(['business_id', 'account_id', 'statement_date'], 'finance_bank_rec_account_date_idx');
                $table->index(['business_id', 'status'], 'finance_bank_rec_status_idx');
                $table->index(['business_id', 'location_id'], 'finance_bank_rec_location_idx');
            });
        }

        if (!Schema::hasTable('finance_bank_reconciliation_lines')) {
            Schema::create('finance_bank_reconciliation_lines', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('reconciliation_id');
                $table->unsignedBigInteger('account_transaction_id')->nullable();
                $table->dateTime('transaction_date')->nullable();
                $table->string('reference', 191)->nullable();
                $table->text('description')->nullable();
                $table->string('transaction_type', 20);
                $table->decimal('amount', 22, 4)->default(0);
                $table->boolean('is_cleared')->default(false);
                $table->timestamps();

                $table->index(['reconciliation_id', 'is_cleared'], 'finance_bank_rec_lines_status_idx');
                $table->index('account_transaction_id', 'finance_bank_rec_lines_tx_idx');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('finance_bank_reconciliation_lines');
        Schema::dropIfExists('finance_bank_reconciliations');
    }
}
