<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reo_receipt_audits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('receipt_id');
            $table->string('field_name', 80);
            $table->string('field_label', 120);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->unsignedBigInteger('edited_by')->nullable();
            $table->string('edited_by_name', 190)->nullable();
            $table->timestamp('edited_at');

            $table->index(['receipt_id', 'edited_at'], 'reo_receipt_audits_receipt_idx');
            $table->foreign('receipt_id', 'reo_receipt_audits_receipt_fk')->references('id')->on('reo_receipts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reo_receipt_audits');
    }
};
