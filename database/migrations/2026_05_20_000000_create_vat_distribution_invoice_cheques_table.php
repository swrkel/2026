<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vat_distribution_invoice_cheques', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('invoice_id');
            $table->string('bank')->nullable();
            $table->string('branch')->nullable();
            $table->string('cheque_no')->nullable();
            $table->date('cheque_date')->nullable();
            $table->decimal('amount', 22, 4)->default(0);
            $table->timestamps();

            $table->foreign('invoice_id')
                ->references('id')
                ->on('vat_distribution_invoices')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vat_distribution_invoice_cheques');
    }
};
