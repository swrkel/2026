<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('restaurant_new_corporate_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('account_code', 50);
            $table->string('company_name');
            $table->string('contact_person')->nullable();
            $table->string('mobile', 30)->nullable();
            $table->string('email')->nullable();
            $table->decimal('credit_limit', 22, 4)->default(0);
            $table->decimal('current_balance', 22, 4)->default(0);
            $table->integer('credit_days')->default(0);
            $table->enum('status', ['active','hold','closed'])->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['business_id','account_code'], 'restnew_corp_account_code_unique');
            $table->index(['business_id','location_id','status'], 'restnew_corp_account_scope_idx');
        });

        Schema::create('restaurant_new_corporate_contract_prices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('corporate_account_id');
            $table->unsignedBigInteger('menu_item_id')->nullable();
            $table->unsignedBigInteger('menu_category_id')->nullable();
            $table->decimal('contract_price', 22, 4)->nullable();
            $table->decimal('discount_percent', 8, 4)->default(0);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->enum('status', ['active','inactive'])->default('active');
            $table->timestamps();
            $table->index(['business_id','corporate_account_id','status'], 'restnew_corp_price_scope_idx');
        });

        Schema::create('restaurant_new_corporate_invoices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('corporate_account_id');
            $table->string('invoice_no', 80);
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 22, 4)->default(0);
            $table->decimal('tax_total', 22, 4)->default(0);
            $table->decimal('discount_total', 22, 4)->default(0);
            $table->decimal('grand_total', 22, 4)->default(0);
            $table->decimal('paid_amount', 22, 4)->default(0);
            $table->decimal('balance_amount', 22, 4)->default(0);
            $table->enum('status', ['draft','issued','partially_paid','paid','cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->unique(['business_id','invoice_no'], 'restnew_corp_invoice_no_unique');
            $table->index(['business_id','location_id','status','invoice_date'], 'restnew_corp_invoice_scope_idx');
        });

        Schema::create('restaurant_new_corporate_invoice_lines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('corporate_invoice_id');
            $table->unsignedBigInteger('restaurant_order_id')->nullable();
            $table->string('description');
            $table->decimal('qty', 22, 4)->default(1);
            $table->decimal('unit_price', 22, 4)->default(0);
            $table->decimal('line_total', 22, 4)->default(0);
            $table->timestamps();
            $table->index(['business_id','corporate_invoice_id'], 'restnew_corp_invoice_line_idx');
        });

        Schema::create('restaurant_new_corporate_payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('corporate_account_id');
            $table->unsignedBigInteger('corporate_invoice_id')->nullable();
            $table->date('payment_date');
            $table->string('method', 50)->nullable();
            $table->string('reference_no', 120)->nullable();
            $table->decimal('amount', 22, 4)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['business_id','corporate_account_id','payment_date'], 'restnew_corp_payment_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_new_corporate_payments');
        Schema::dropIfExists('restaurant_new_corporate_invoice_lines');
        Schema::dropIfExists('restaurant_new_corporate_invoices');
        Schema::dropIfExists('restaurant_new_corporate_contract_prices');
        Schema::dropIfExists('restaurant_new_corporate_accounts');
    }
};
