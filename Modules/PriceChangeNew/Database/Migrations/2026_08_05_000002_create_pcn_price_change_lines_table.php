<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('pcn_price_change_lines')) return;
        Schema::create('pcn_price_change_lines', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('price_change_id')->index();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('line_no');
            $table->unsignedInteger('product_id')->index();
            $table->unsignedInteger('variation_id')->index();
            $table->string('product_name', 191);
            $table->string('variation_name', 255);
            $table->string('sku', 191)->nullable()->index();
            $table->unsignedInteger('tax_id')->nullable()->index();
            $table->string('tax_name', 191)->nullable();
            $table->decimal('tax_rate', 12, 6)->default(0);
            $table->string('tax_type', 20)->default('exclusive');
            $table->decimal('stock_quantity', 22, 4)->default(0);
            $table->decimal('current_purchase_price_ex_tax', 22, 8)->default(0);
            $table->decimal('current_purchase_price_inc_tax', 22, 8)->default(0);
            $table->decimal('current_sell_price_ex_tax', 22, 8)->default(0);
            $table->decimal('current_sell_price_inc_tax', 22, 8)->default(0);
            $table->decimal('current_profit_percent', 22, 8)->nullable();
            $table->string('purchase_price_basis', 20)->default('inc_tax');
            $table->decimal('new_purchase_price_ex_tax', 22, 8)->nullable();
            $table->decimal('new_purchase_price_inc_tax', 22, 8)->nullable();
            $table->string('sell_price_basis', 20)->default('inc_tax');
            $table->decimal('new_sell_price_ex_tax', 22, 8);
            $table->decimal('new_sell_price_inc_tax', 22, 8);
            $table->decimal('new_profit_percent', 22, 8)->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['price_change_id','variation_id'], 'pcn_lines_change_variation_unique');
            $table->index(['business_id','product_id','variation_id'], 'pcn_lines_business_product_variation_idx');
        });
    }
    public function down(): void { Schema::dropIfExists('pcn_price_change_lines'); }
};
