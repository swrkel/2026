<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vat_invoice_to_transaction_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('business_id');
            $table->boolean('auto_update')->default(0);
            $table->string('status')->default('Active');
            $table->integer('created_by');
            $table->timestamps();
        });

        Schema::create('vat_invoice_to_transaction_history', function (Blueprint $table) {
            $table->id();
            $table->integer('business_id');
            $table->integer('setting_id');
            $table->string('original_status');
            $table->string('changed_status');
            $table->integer('changed_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vat_invoice_to_transaction_history');
        Schema::dropIfExists('vat_invoice_to_transaction_settings');
    }
};
