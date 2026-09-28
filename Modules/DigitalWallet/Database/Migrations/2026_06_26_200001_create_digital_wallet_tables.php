<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_wallets', function (Blueprint $table) {
            $table->id();
            $table->string('wallet_code')->unique();
            $table->string('wallet_name');
            $table->string('wallet_type')->default('general');
            $table->string('owner_type')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->string('currency', 10)->default('LKR');
            $table->decimal('available_balance', 22, 6)->default(0);
            $table->decimal('reserved_balance', 22, 6)->default(0);
            $table->decimal('total_balance', 22, 6)->default(0);
            $table->decimal('low_balance_threshold', 22, 6)->nullable();
            $table->string('status')->default('active');
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('digital_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_no')->unique();
            $table->unsignedBigInteger('wallet_id')->index();
            $table->string('transaction_type');
            $table->decimal('amount', 22, 6);
            $table->string('currency', 10)->default('LKR');
            $table->decimal('balance_before', 22, 6)->default(0);
            $table->decimal('balance_after', 22, 6)->default(0);
            $table->string('source_module')->nullable()->index();
            $table->string('source_reference')->nullable()->index();
            $table->string('external_reference')->nullable();
            $table->string('status')->default('completed');
            $table->text('note')->nullable();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('digital_wallet_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wallet_id')->index();
            $table->unsignedBigInteger('transaction_id')->nullable()->index();
            $table->string('entry_type');
            $table->decimal('debit', 22, 6)->default(0);
            $table->decimal('credit', 22, 6)->default(0);
            $table->decimal('balance', 22, 6)->default(0);
            $table->string('currency', 10)->default('LKR');
            $table->dateTime('entry_date')->nullable()->index();
            $table->string('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('digital_wallet_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_name');
            $table->string('rule_type');
            $table->string('wallet_type')->nullable();
            $table->decimal('minimum_balance', 22, 6)->nullable();
            $table->decimal('maximum_balance', 22, 6)->nullable();
            $table->decimal('daily_limit', 22, 6)->nullable();
            $table->decimal('monthly_limit', 22, 6)->nullable();
            $table->decimal('approval_threshold', 22, 6)->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('conditions')->nullable();
            $table->json('actions')->nullable();
            $table->timestamps();
        });

        Schema::create('digital_wallet_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key')->unique();
            $table->text('setting_value')->nullable();
            $table->string('setting_type')->default('string');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_wallet_settings');
        Schema::dropIfExists('digital_wallet_rules');
        Schema::dropIfExists('digital_wallet_ledger_entries');
        Schema::dropIfExists('digital_wallet_transactions');
        Schema::dropIfExists('digital_wallets');
    }
};
