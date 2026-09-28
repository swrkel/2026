<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('beauty_salon_product_categories', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('business_id')->nullable()->index(); $table->string('name'); $table->boolean('is_active')->default(true); $table->timestamps(); });
        Schema::create('beauty_salon_product_brands', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('business_id')->nullable()->index(); $table->string('name'); $table->boolean('is_active')->default(true); $table->timestamps(); });
        Schema::create('beauty_salon_suppliers', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('business_id')->nullable()->index(); $table->string('name'); $table->string('mobile')->nullable(); $table->string('email')->nullable(); $table->text('address')->nullable(); $table->timestamps(); });
        Schema::create('beauty_salon_products', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('business_id')->nullable()->index(); $table->unsignedBigInteger('category_id')->nullable()->index(); $table->unsignedBigInteger('brand_id')->nullable()->index(); $table->string('sku')->nullable()->index(); $table->string('barcode')->nullable()->index(); $table->string('name'); $table->decimal('purchase_price', 20, 4)->default(0); $table->decimal('selling_price', 20, 4)->default(0); $table->decimal('min_stock', 20, 4)->default(0); $table->boolean('track_batch')->default(false); $table->boolean('track_expiry')->default(false); $table->boolean('is_active')->default(true); $table->timestamps(); });
        Schema::create('beauty_salon_product_batches', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('business_id')->nullable()->index(); $table->unsignedBigInteger('product_id')->index(); $table->string('batch_no')->nullable(); $table->date('expiry_date')->nullable()->index(); $table->decimal('quantity', 20, 4)->default(0); $table->timestamps(); });
        Schema::create('beauty_salon_stock_movements', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('business_id')->nullable()->index(); $table->unsignedBigInteger('business_location_id')->nullable()->index(); $table->unsignedBigInteger('product_id')->index(); $table->unsignedBigInteger('batch_id')->nullable()->index(); $table->string('movement_type')->index(); $table->decimal('quantity', 20, 4); $table->decimal('unit_cost', 20, 4)->default(0); $table->string('reference_type')->nullable(); $table->unsignedBigInteger('reference_id')->nullable(); $table->dateTime('transaction_date')->nullable()->index(); $table->text('note')->nullable(); $table->timestamps(); });
        Schema::create('beauty_salon_retail_sales', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('business_id')->nullable()->index(); $table->unsignedBigInteger('business_location_id')->nullable()->index(); $table->string('invoice_no')->nullable()->index(); $table->unsignedBigInteger('customer_id')->nullable()->index(); $table->dateTime('sale_date')->nullable()->index(); $table->decimal('gross_total', 20, 4)->default(0); $table->decimal('discount_total', 20, 4)->default(0); $table->decimal('tax_total', 20, 4)->default(0); $table->decimal('net_total', 20, 4)->default(0); $table->string('payment_status')->default('due'); $table->timestamps(); });
        Schema::create('beauty_salon_retail_sale_lines', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('retail_sale_id')->index(); $table->unsignedBigInteger('product_id')->index(); $table->decimal('quantity', 20, 4); $table->decimal('unit_price', 20, 4); $table->decimal('discount', 20, 4)->default(0); $table->decimal('line_total', 20, 4); $table->timestamps(); });
    }
    public function down(): void
    {
        Schema::dropIfExists('beauty_salon_retail_sale_lines');
        Schema::dropIfExists('beauty_salon_retail_sales');
        Schema::dropIfExists('beauty_salon_stock_movements');
        Schema::dropIfExists('beauty_salon_product_batches');
        Schema::dropIfExists('beauty_salon_products');
        Schema::dropIfExists('beauty_salon_suppliers');
        Schema::dropIfExists('beauty_salon_product_brands');
        Schema::dropIfExists('beauty_salon_product_categories');
    }
};
