<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bs_voucher_series', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->nullable()->index();
            $table->string('name');
            $table->string('prefix', 20)->default('BSV');
            $table->unsignedInteger('last_number')->default(0);
            $table->unsignedTinyInteger('padding')->default(6);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bs_gift_vouchers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->nullable()->index();
            $table->unsignedInteger('business_location_id')->nullable()->index();
            $table->string('voucher_no')->unique();
            $table->string('voucher_type')->default('gift_voucher');
            $table->decimal('original_amount', 22, 4)->default(0);
            $table->decimal('balance_amount', 22, 4)->default(0);
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable()->index();
            $table->unsignedInteger('customer_id')->nullable()->index();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_mobile')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamp('sold_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('bs_gift_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->nullable()->index();
            $table->string('card_no')->unique();
            $table->decimal('original_amount', 22, 4)->default(0);
            $table->decimal('balance_amount', 22, 4)->default(0);
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->unsignedInteger('customer_id')->nullable()->index();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('bs_voucher_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('voucher_id')->nullable()->index();
            $table->unsignedBigInteger('gift_card_id')->nullable()->index();
            $table->unsignedInteger('business_id')->nullable()->index();
            $table->string('transaction_type')->index();
            $table->decimal('amount', 22, 4)->default(0);
            $table->decimal('balance_after', 22, 4)->nullable();
            $table->unsignedInteger('transaction_id')->nullable()->index();
            $table->unsignedInteger('created_by')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('bs_voucher_redemptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('voucher_id')->nullable()->index();
            $table->unsignedBigInteger('gift_card_id')->nullable()->index();
            $table->unsignedInteger('business_id')->nullable()->index();
            $table->unsignedInteger('business_location_id')->nullable()->index();
            $table->decimal('redeemed_amount', 22, 4)->default(0);
            $table->unsignedInteger('sale_transaction_id')->nullable()->index();
            $table->dateTime('redeemed_at')->nullable();
            $table->unsignedInteger('redeemed_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bs_voucher_redemptions');
        Schema::dropIfExists('bs_voucher_transactions');
        Schema::dropIfExists('bs_gift_cards');
        Schema::dropIfExists('bs_gift_vouchers');
        Schema::dropIfExists('bs_voucher_series');
    }
};
