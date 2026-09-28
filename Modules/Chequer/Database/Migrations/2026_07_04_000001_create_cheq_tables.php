<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('cheq_templates')) {
            Schema::create('cheq_templates', function (Blueprint $table) {
                $table->id(); $table->unsignedBigInteger('business_id')->index(); $table->string('template_name'); $table->string('bank_name')->nullable(); $table->decimal('paper_width', 10, 2)->nullable(); $table->decimal('paper_height', 10, 2)->nullable(); $table->json('field_map')->nullable(); $table->string('status', 20)->default('active'); $table->timestamps();
            });
        }
        if (!Schema::hasTable('cheq_cheque_books')) {
            Schema::create('cheq_cheque_books', function (Blueprint $table) {
                $table->id(); $table->unsignedBigInteger('business_id')->index(); $table->unsignedBigInteger('account_id')->index(); $table->string('book_no'); $table->bigInteger('start_no'); $table->bigInteger('end_no'); $table->bigInteger('next_no'); $table->string('status', 20)->default('active'); $table->timestamps();
            });
        }
        if (!Schema::hasTable('cheq_cheques')) {
            Schema::create('cheq_cheques', function (Blueprint $table) {
                $table->id(); $table->unsignedBigInteger('business_id')->index(); $table->unsignedBigInteger('cheq_cheque_book_id')->index(); $table->string('cheque_no')->index(); $table->date('cheque_date'); $table->string('payee_name'); $table->decimal('amount', 22, 4)->default(0); $table->string('payment_type', 50); $table->text('memo')->nullable(); $table->string('status', 30)->default('draft'); $table->timestamp('printed_at')->nullable(); $table->timestamps();
            });
        }
        if (!Schema::hasTable('cheq_default_settings')) {
            Schema::create('cheq_default_settings', function (Blueprint $table) {
                $table->id(); $table->unsignedBigInteger('business_id')->unique(); $table->unsignedBigInteger('default_bank_account_id')->nullable()->comment('references accounts.id where account group is Bank'); $table->unsignedBigInteger('default_template_id')->nullable(); $table->string('default_currency', 20)->nullable(); $table->string('default_font')->nullable(); $table->decimal('default_font_size', 8, 2)->nullable(); $table->timestamps();
            });
        }
        if (!Schema::hasTable('cheq_audit_logs')) {
            Schema::create('cheq_audit_logs', function (Blueprint $table) {
                $table->id(); $table->unsignedBigInteger('business_id')->index(); $table->unsignedBigInteger('user_id')->nullable()->index(); $table->string('action'); $table->string('entity')->nullable(); $table->unsignedBigInteger('entity_id')->nullable(); $table->json('payload')->nullable(); $table->timestamps();
            });
        }
    }
    public function down(): void
    {
        Schema::dropIfExists('cheq_audit_logs'); Schema::dropIfExists('cheq_default_settings'); Schema::dropIfExists('cheq_cheques'); Schema::dropIfExists('cheq_cheque_books'); Schema::dropIfExists('cheq_templates');
    }
};
