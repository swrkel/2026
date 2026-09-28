<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stn_delivery_confirmations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('store_id')->nullable()->index();
            $table->unsignedBigInteger('transfer_id')->unique();
            $table->dateTime('delivered_at')->nullable()->index();
            $table->string('received_by')->nullable();
            $table->string('receiver_mobile', 50)->nullable();
            $table->string('condition_status', 50)->default('good')->index();
            $table->text('remarks')->nullable();
            $table->longText('signature_data')->nullable();
            $table->string('photo_reference')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestamps();
        });

        Schema::create('stn_delivery_damages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_confirmation_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->decimal('qty', 22, 6)->default(0);
            $table->string('damage_type', 100);
            $table->decimal('estimated_value', 22, 6)->default(0);
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stn_delivery_damages');
        Schema::dropIfExists('stn_delivery_confirmations');
    }
};
