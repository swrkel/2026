<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reo_receipt_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('receipt_id');
            $table->string('item_type', 30);
            $table->unsignedBigInteger('item_id');
            $table->string('source_detail', 190);
            $table->decimal('amount', 22, 8)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('created_at')->nullable();

            $table->index(['receipt_id', 'sort_order'], 'reo_receipt_details_order_idx');
            $table->foreign('receipt_id', 'reo_receipt_details_receipt_fk')->references('id')->on('reo_receipts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reo_receipt_details');
    }
};
