<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_hub_sms_packages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->string('code', 100)->nullable()->index();
            $table->string('name');
            $table->unsignedInteger('credits')->default(0);
            $table->decimal('cost_price', 18, 4)->default(0);
            $table->decimal('selling_price', 18, 4)->default(0);
            $table->decimal('profit_amount', 18, 4)->default(0);
            $table->unsignedInteger('validity_days')->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('communication_hub_clients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('parent_client_id')->nullable()->index();
            $table->string('name');
            $table->string('business_name')->nullable();
            $table->string('mobile', 30)->index();
            $table->string('email')->nullable()->index();
            $table->string('client_type', 50)->default('business')->index();
            $table->string('status', 30)->default('active')->index();
            $table->boolean('api_enabled')->default(false);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('communication_hub_wallets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('client_id')->unique();
            $table->integer('available_credits')->default(0);
            $table->integer('reserved_credits')->default(0);
            $table->integer('total_purchased')->default(0);
            $table->integer('total_used')->default(0);
            $table->integer('low_balance_alert_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('status', 30)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('communication_hub_wallet_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->unsignedBigInteger('wallet_id')->nullable()->index();
            $table->unsignedBigInteger('package_id')->nullable()->index();
            $table->string('type', 50)->index();
            $table->integer('credits')->default(0);
            $table->integer('opening_balance')->default(0);
            $table->integer('closing_balance')->default(0);
            $table->decimal('amount', 18, 4)->default(0);
            $table->decimal('profit_amount', 18, 4)->default(0);
            $table->string('reference')->nullable()->index();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('communication_hub_sender_ids', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->unsignedBigInteger('provider_id')->nullable()->index();
            $table->string('sender_id', 100)->index();
            $table->string('channel', 50)->default('sms');
            $table->string('status', 30)->default('active')->index();
            $table->boolean('is_default')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_hub_sender_ids');
        Schema::dropIfExists('communication_hub_wallet_transactions');
        Schema::dropIfExists('communication_hub_wallets');
        Schema::dropIfExists('communication_hub_clients');
        Schema::dropIfExists('communication_hub_sms_packages');
    }
};
