<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::create('bkg_card_disputes', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('business_id')->nullable()->index(); $table->unsignedBigInteger('atm_transaction_id')->nullable()->index(); $table->unsignedBigInteger('debit_card_id')->nullable()->index(); $table->string('case_no')->unique(); $table->string('reason')->nullable(); $table->decimal('disputed_amount', 22, 4)->default(0); $table->string('status')->default('open'); $table->date('resolved_date')->nullable(); $table->text('resolution_note')->nullable(); $table->timestamps(); }); }
    public function down(): void { Schema::dropIfExists('bkg_card_disputes'); }
};
