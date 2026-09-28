<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBankingInsuranceTables extends Migration
{
    public function up()
    {
        Schema::create('banking_insurance_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->string('name');
            $table->string('code')->nullable();
            $table->enum('insurance_type', ['life','general','vehicle','property','health','loan_protection','other'])->default('general');
            $table->decimal('default_sum_assured', 22, 4)->default(0);
            $table->decimal('default_premium', 22, 4)->default(0);
            $table->decimal('commission_rate', 8, 4)->default(0);
            $table->integer('term_months')->default(12);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('banking_insurance_policies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->unsignedInteger('customer_id')->nullable()->index();
            $table->string('policy_no')->index();
            $table->string('customer_name');
            $table->string('mobile')->nullable();
            $table->string('nic_no')->nullable();
            $table->string('nominee_name')->nullable();
            $table->string('nominee_mobile')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('sum_assured', 22, 4)->default(0);
            $table->decimal('premium_amount', 22, 4)->default(0);
            $table->enum('premium_frequency', ['one_time','monthly','quarterly','half_yearly','yearly'])->default('monthly');
            $table->enum('status', ['draft','active','lapsed','cancelled','matured'])->default('draft')->index();
            $table->text('remarks')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'policy_no']);
        });

        Schema::create('banking_insurance_premiums', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('policy_id')->index();
            $table->string('receipt_no')->index();
            $table->date('payment_date');
            $table->decimal('amount', 22, 4)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('reference_no')->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'receipt_no']);
        });

        Schema::create('banking_insurance_claims', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('policy_id')->index();
            $table->string('claim_no')->index();
            $table->date('claim_date');
            $table->decimal('claim_amount', 22, 4)->default(0);
            $table->decimal('approved_amount', 22, 4)->default(0);
            $table->enum('status', ['submitted','under_review','approved','rejected','settled'])->default('submitted')->index();
            $table->text('reason')->nullable();
            $table->text('settlement_note')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'claim_no']);
        });

        Schema::create('banking_insurance_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index();
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'key']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('banking_insurance_settings');
        Schema::dropIfExists('banking_insurance_claims');
        Schema::dropIfExists('banking_insurance_premiums');
        Schema::dropIfExists('banking_insurance_policies');
        Schema::dropIfExists('banking_insurance_products');
    }
}
