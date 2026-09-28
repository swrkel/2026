<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reo_receipt_cheques', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('receipt_id');
            $table->string('external_payment_id', 100)->nullable();
            $table->string('cheque_number', 190)->nullable();
            $table->string('bank_name', 190)->nullable();
            $table->date('cheque_date')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('receipt_id', 'reo_receipt_cheques_receipt_idx');
            $table->foreign('receipt_id', 'reo_receipt_cheques_receipt_fk')->references('id')->on('reo_receipts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reo_receipt_cheques');
    }
};
