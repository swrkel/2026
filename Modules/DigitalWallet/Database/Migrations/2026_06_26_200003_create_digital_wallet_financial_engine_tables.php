<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_wallet_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reservation_no')->unique();
            $table->unsignedBigInteger('wallet_id')->index();
            $table->unsignedBigInteger('transaction_id')->nullable()->index();
            $table->decimal('amount', 22, 6);
            $table->string('currency', 10)->default('LKR');
            $table->string('source_module')->nullable()->index();
            $table->string('source_reference')->nullable()->index();
            $table->string('status')->default('reserved')->index();
            $table->dateTime('expires_at')->nullable()->index();
            $table->dateTime('committed_at')->nullable();
            $table->dateTime('released_at')->nullable();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('digital_wallet_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_no')->unique();
            $table->unsignedBigInteger('wallet_id')->index();
            $table->string('adjustment_type')->default('credit');
            $table->decimal('amount', 22, 6);
            $table->string('currency', 10)->default('LKR');
            $table->string('reason')->nullable();
            $table->string('status')->default('completed')->index();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->dateTime('approved_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_wallet_adjustments');
        Schema::dropIfExists('digital_wallet_reservations');
    }
};
