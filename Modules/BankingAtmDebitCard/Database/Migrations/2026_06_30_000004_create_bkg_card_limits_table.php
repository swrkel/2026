<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::create('bkg_card_limits', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('business_id')->nullable()->index(); $table->unsignedBigInteger('debit_card_id')->index(); $table->decimal('atm_daily_limit', 22, 4)->default(0); $table->decimal('pos_daily_limit', 22, 4)->default(0); $table->decimal('ecommerce_daily_limit', 22, 4)->default(0); $table->boolean('atm_enabled')->default(true); $table->boolean('pos_enabled')->default(true); $table->boolean('ecommerce_enabled')->default(false); $table->timestamps(); }); }
    public function down(): void { Schema::dropIfExists('bkg_card_limits'); }
};
