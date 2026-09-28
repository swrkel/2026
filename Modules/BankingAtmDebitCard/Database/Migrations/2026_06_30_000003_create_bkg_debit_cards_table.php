<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::create('bkg_debit_cards', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('business_id')->nullable()->index(); $table->unsignedBigInteger('location_id')->nullable()->index(); $table->unsignedBigInteger('customer_id')->nullable()->index(); $table->unsignedBigInteger('deposit_account_id')->nullable()->index(); $table->unsignedBigInteger('card_inventory_id')->nullable()->index(); $table->string('card_number_masked')->index(); $table->string('status')->default('pending'); $table->date('issued_date')->nullable(); $table->date('activated_date')->nullable(); $table->date('expiry_date')->nullable(); $table->text('remarks')->nullable(); $table->unsignedBigInteger('created_by')->nullable(); $table->timestamps(); }); }
    public function down(): void { Schema::dropIfExists('bkg_debit_cards'); }
};
