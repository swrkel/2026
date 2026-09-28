<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pos_plugins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->string('code', 80)->index();
            $table->string('name');
            $table->string('version', 40)->default('1.0.0');
            $table->boolean('is_enabled')->default(false)->index();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'code']);
        });

        Schema::create('pos_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('business_location_id')->nullable()->index();
            $table->unsignedBigInteger('sale_id')->nullable()->index();
            $table->string('return_no')->index();
            $table->string('return_type')->default('refund');
            $table->dateTime('transaction_date')->nullable()->index();
            $table->decimal('subtotal', 22, 4)->default(0);
            $table->decimal('tax_amount', 22, 4)->default(0);
            $table->decimal('discount_amount', 22, 4)->default(0);
            $table->decimal('total_amount', 22, 4)->default(0);
            $table->string('status')->default('draft')->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('pos_return_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('return_id')->index();
            $table->unsignedBigInteger('sale_line_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->string('description')->nullable();
            $table->decimal('quantity', 22, 4)->default(0);
            $table->decimal('unit_price', 22, 4)->default(0);
            $table->decimal('line_total', 22, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('pos_cash_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('business_location_id')->nullable()->index();
            $table->unsignedBigInteger('register_session_id')->nullable()->index();
            $table->string('movement_type')->index();
            $table->decimal('amount', 22, 4)->default(0);
            $table->dateTime('transaction_date')->nullable()->index();
            $table->string('reference_no')->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('pos_discounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->string('name');
            $table->string('discount_type')->default('fixed');
            $table->decimal('value', 22, 4)->default(0);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->json('rules')->nullable();
            $table->timestamps();
        });

        Schema::create('pos_coupons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->string('code')->index();
            $table->string('name')->nullable();
            $table->decimal('value', 22, 4)->default(0);
            $table->string('coupon_type')->default('fixed');
            $table->date('expires_on')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['business_id','code']);
        });

        Schema::create('pos_gift_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->string('card_no')->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->decimal('opening_amount', 22, 4)->default(0);
            $table->decimal('balance_amount', 22, 4)->default(0);
            $table->date('expires_on')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->unique(['business_id','card_no']);
        });

        Schema::create('pos_receipt_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->string('name');
            $table->string('template_type')->default('thermal');
            $table->boolean('is_default')->default(false)->index();
            $table->longText('html_template')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_receipt_templates');
        Schema::dropIfExists('pos_gift_cards');
        Schema::dropIfExists('pos_coupons');
        Schema::dropIfExists('pos_discounts');
        Schema::dropIfExists('pos_cash_movements');
        Schema::dropIfExists('pos_return_lines');
        Schema::dropIfExists('pos_returns');
        Schema::dropIfExists('pos_plugins');
    }
};
