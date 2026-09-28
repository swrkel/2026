<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBankingMobileBankingTables extends Migration
{
    public function up()
    {
        Schema::create('banking_mobile_customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('mobile_number', 50)->nullable()->index();
            $table->string('login_code', 80)->nullable()->index();
            $table->string('status', 30)->default('pending');
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
        });
        Schema::create('banking_mobile_devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mobile_customer_id')->nullable()->index();
            $table->string('device_uuid', 150)->nullable()->index();
            $table->string('device_name')->nullable();
            $table->string('platform', 50)->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
        Schema::create('banking_mobile_beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mobile_customer_id')->nullable()->index();
            $table->string('beneficiary_name');
            $table->string('account_number', 80)->nullable();
            $table->string('bank_code', 80)->nullable();
            $table->decimal('daily_limit', 22, 4)->default(0);
            $table->string('status', 30)->default('pending');
            $table->timestamps();
        });
        Schema::create('banking_mobile_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mobile_customer_id')->nullable()->index();
            $table->string('reference_no', 80)->nullable()->index();
            $table->string('from_account', 80)->nullable();
            $table->string('to_account', 80)->nullable();
            $table->decimal('amount', 22, 4)->default(0);
            $table->string('status', 30)->default('pending');
            $table->timestamps();
        });
        Schema::create('banking_mobile_bill_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mobile_customer_id')->nullable()->index();
            $table->string('biller_code', 80)->nullable();
            $table->string('consumer_reference', 120)->nullable();
            $table->decimal('amount', 22, 4)->default(0);
            $table->string('status', 30)->default('pending');
            $table->timestamps();
        });
        Schema::create('banking_mobile_qr_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mobile_customer_id')->nullable()->index();
            $table->string('merchant_reference', 120)->nullable();
            $table->string('qr_reference', 120)->nullable()->index();
            $table->decimal('amount', 22, 4)->default(0);
            $table->string('status', 30)->default('pending');
            $table->timestamps();
        });
        Schema::create('banking_mobile_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mobile_customer_id')->nullable()->index();
            $table->boolean('sms_enabled')->default(true);
            $table->boolean('email_enabled')->default(true);
            $table->boolean('push_enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('banking_mobile_notification_preferences');
        Schema::dropIfExists('banking_mobile_qr_payments');
        Schema::dropIfExists('banking_mobile_bill_payments');
        Schema::dropIfExists('banking_mobile_transfers');
        Schema::dropIfExists('banking_mobile_beneficiaries');
        Schema::dropIfExists('banking_mobile_devices');
        Schema::dropIfExists('banking_mobile_customers');
    }
}
