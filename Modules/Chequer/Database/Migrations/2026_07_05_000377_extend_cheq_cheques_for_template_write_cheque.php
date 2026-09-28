<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('cheq_cheques')) {
            return;
        }

        Schema::table('cheq_cheques', function (Blueprint $table) {
            if (!Schema::hasColumn('cheq_cheques', 'cheq_template_id')) $table->unsignedBigInteger('cheq_template_id')->nullable()->after('cheq_cheque_book_id')->index();
            if (!Schema::hasColumn('cheq_cheques', 'payee_type')) $table->string('payee_type', 80)->nullable()->after('payee_name');
            if (!Schema::hasColumn('cheq_cheques', 'payee_id')) $table->unsignedBigInteger('payee_id')->nullable()->after('payee_type')->index();
            if (!Schema::hasColumn('cheq_cheques', 'payment_for')) $table->string('payment_for', 120)->nullable()->after('payment_type');
            if (!Schema::hasColumn('cheq_cheques', 'purchase_id')) $table->string('purchase_id', 120)->nullable()->after('payment_for');
            if (!Schema::hasColumn('cheq_cheques', 'purchase_bill_no')) $table->string('purchase_bill_no')->nullable()->after('purchase_id');
            if (!Schema::hasColumn('cheq_cheques', 'supplier_order_no')) $table->string('supplier_order_no')->nullable()->after('purchase_bill_no');
            if (!Schema::hasColumn('cheq_cheques', 'payable_amount')) $table->decimal('payable_amount', 22, 4)->nullable()->after('supplier_order_no');
            if (!Schema::hasColumn('cheq_cheques', 'payment_status')) $table->string('payment_status', 80)->nullable()->after('payable_amount');
            if (!Schema::hasColumn('cheq_cheques', 'bank_account_id')) $table->unsignedBigInteger('bank_account_id')->nullable()->after('payment_status')->index();
            if (!Schema::hasColumn('cheq_cheques', 'stamp_id')) $table->string('stamp_id', 120)->nullable()->after('bank_account_id');
            if (!Schema::hasColumn('cheq_cheques', 'date_condition')) $table->string('date_condition', 80)->nullable()->after('stamp_id');
            if (!Schema::hasColumn('cheq_cheques', 'currency_id')) $table->string('currency_id', 80)->nullable()->after('date_condition');
            if (!Schema::hasColumn('cheq_cheques', 'currency_code')) $table->string('currency_code', 20)->nullable()->after('currency_id');
            if (!Schema::hasColumn('cheq_cheques', 'on_account_of')) $table->string('on_account_of', 500)->nullable()->after('currency_code');
            if (!Schema::hasColumn('cheq_cheques', 'amount_words')) $table->string('amount_words', 1000)->nullable()->after('on_account_of');
            if (!Schema::hasColumn('cheq_cheques', 'date_time')) $table->string('date_time', 80)->nullable()->after('amount_words');
            if (!Schema::hasColumn('cheq_cheques', 'double_entry_account_id')) $table->unsignedBigInteger('double_entry_account_id')->nullable()->after('date_time')->index();
            if (!Schema::hasColumn('cheq_cheques', 'options')) $table->json('options')->nullable()->after('double_entry_account_id');
            if (!Schema::hasColumn('cheq_cheques', 'attachment_path')) $table->string('attachment_path')->nullable()->after('options');
        });
    }

    public function down(): void
    {
        // Tenant-safe migration: do not drop user data columns automatically.
    }
};
