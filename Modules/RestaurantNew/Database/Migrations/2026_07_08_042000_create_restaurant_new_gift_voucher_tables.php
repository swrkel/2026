<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rn_gift_vouchers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('business_location_id')->nullable()->index();
            $table->string('voucher_no', 40)->index();
            $table->string('voucher_type', 30)->default('gift_card');
            $table->string('customer_name')->nullable();
            $table->string('customer_mobile', 30)->nullable();
            $table->string('customer_email')->nullable();
            $table->decimal('issue_amount', 22, 4)->default(0);
            $table->decimal('balance_amount', 22, 4)->default(0);
            $table->date('issued_on')->nullable();
            $table->date('expires_on')->nullable();
            $table->string('status', 30)->default('active')->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();
            $table->unique(['business_id', 'voucher_no'], 'rn_gift_vouchers_business_voucher_unique');
        });

        Schema::create('rn_gift_voucher_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('business_location_id')->nullable()->index();
            $table->unsignedBigInteger('gift_voucher_id')->index();
            $table->string('transaction_type', 30)->index();
            $table->string('reference_type', 60)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable()->index();
            $table->decimal('amount', 22, 4)->default(0);
            $table->decimal('balance_after', 22, 4)->default(0);
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rn_gift_voucher_transactions');
        Schema::dropIfExists('rn_gift_vouchers');
    }
};
