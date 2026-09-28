<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bs_wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->nullable()->index();
            $table->unsignedInteger('business_location_id')->nullable()->index();
            $table->unsignedInteger('customer_id')->nullable()->index();
            $table->string('wallet_no')->nullable()->index();
            $table->string('customer_name')->nullable();
            $table->string('customer_mobile')->nullable();
            $table->decimal('opening_balance', 22, 4)->default(0);
            $table->decimal('balance', 22, 4)->default(0);
            $table->string('status')->default('active')->index();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('bs_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wallet_id')->index();
            $table->unsignedInteger('business_id')->nullable()->index();
            $table->unsignedInteger('business_location_id')->nullable()->index();
            $table->string('transaction_no')->nullable()->index();
            $table->date('transaction_date')->index();
            $table->string('transaction_type')->index();
            $table->string('direction', 10)->index();
            $table->decimal('amount', 22, 4)->default(0);
            $table->decimal('balance_after', 22, 4)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('reference_no')->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('bs_prepaid_packages', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->nullable()->index();
            $table->unsignedInteger('business_location_id')->nullable()->index();
            $table->string('package_code')->nullable()->index();
            $table->string('package_name');
            $table->string('package_type')->default('service')->index();
            $table->decimal('sale_price', 22, 4)->default(0);
            $table->integer('valid_days')->default(0);
            $table->string('status')->default('active')->index();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('bs_prepaid_package_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prepaid_package_id')->index();
            $table->string('item_type')->default('service')->index();
            $table->unsignedBigInteger('item_id')->nullable()->index();
            $table->string('item_name');
            $table->decimal('qty', 22, 4)->default(1);
            $table->decimal('value', 22, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('bs_prepaid_package_sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->nullable()->index();
            $table->unsignedInteger('business_location_id')->nullable()->index();
            $table->unsignedBigInteger('prepaid_package_id')->index();
            $table->unsignedInteger('customer_id')->nullable()->index();
            $table->string('customer_name')->nullable();
            $table->date('sale_date')->index();
            $table->date('expiry_date')->nullable()->index();
            $table->decimal('sale_amount', 22, 4)->default(0);
            $table->decimal('remaining_value', 22, 4)->default(0);
            $table->string('status')->default('active')->index();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('bs_prepaid_package_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('package_sale_id')->index();
            $table->date('usage_date')->index();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('amount', 22, 4)->default(0);
            $table->decimal('remaining_value', 22, 4)->default(0);
            $table->text('note')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bs_prepaid_package_usages');
        Schema::dropIfExists('bs_prepaid_package_sales');
        Schema::dropIfExists('bs_prepaid_package_lines');
        Schema::dropIfExists('bs_prepaid_packages');
        Schema::dropIfExists('bs_wallet_transactions');
        Schema::dropIfExists('bs_wallets');
    }
};
