<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::create('bkg_atm_transactions', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('business_id')->nullable()->index(); $table->unsignedBigInteger('debit_card_id')->nullable()->index(); $table->string('terminal_id')->nullable()->index(); $table->string('transaction_reference')->nullable()->unique(); $table->timestamp('transaction_time')->nullable(); $table->string('transaction_type')->nullable(); $table->decimal('amount', 22, 4)->default(0); $table->decimal('fee', 22, 4)->default(0); $table->string('status')->default('pending'); $table->boolean('is_reconciled')->default(false); $table->timestamps(); }); }
    public function down(): void { Schema::dropIfExists('bkg_atm_transactions'); }
};
