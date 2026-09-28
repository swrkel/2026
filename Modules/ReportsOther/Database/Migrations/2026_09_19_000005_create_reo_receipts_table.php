<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reo_receipts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('store_id')->nullable();
            $table->string('scope_key', 120);
            $table->date('receipt_date');
            $table->string('receipt_no', 80);
            $table->unsignedBigInteger('source_id');
            $table->string('source_name', 120);
            $table->string('membership_no', 120)->nullable();
            $table->boolean('membership_is_manual')->default(false);
            $table->decimal('total_amount', 22, 8)->default(0);
            $table->text('amount_in_words')->nullable();
            $table->unsignedBigInteger('entered_by')->nullable();
            $table->string('entered_by_name', 190)->nullable();
            $table->timestamps();

            $table->unique(['scope_key', 'receipt_no'], 'reo_receipt_scope_no_unique');
            $table->unique(['scope_key', 'receipt_date', 'source_id'], 'reo_receipt_source_date_unique');
            $table->index(['business_id', 'location_id', 'store_id', 'receipt_date'], 'reo_receipt_scope_date_idx');
            $table->index('source_id', 'reo_receipt_source_idx');
            $table->foreign('source_id', 'reo_receipt_source_fk')->references('id')->on('reo_sources')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reo_receipts');
    }
};
