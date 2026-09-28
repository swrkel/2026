<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::create('bkg_card_inventories', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('business_id')->nullable()->index(); $table->unsignedBigInteger('location_id')->nullable()->index(); $table->unsignedBigInteger('card_product_id')->nullable()->index(); $table->string('card_number_masked')->index(); $table->string('card_token')->nullable()->index(); $table->string('stock_status')->default('in_stock'); $table->date('received_date')->nullable(); $table->timestamps(); }); }
    public function down(): void { Schema::dropIfExists('bkg_card_inventories'); }
};
