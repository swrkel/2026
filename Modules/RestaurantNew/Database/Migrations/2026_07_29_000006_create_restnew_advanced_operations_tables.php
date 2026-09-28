<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('restnew_suppliers')) Schema::create('restnew_suppliers', function (Blueprint $t) {
            $t->bigIncrements('id'); $t->unsignedBigInteger('business_id')->index(); $t->unsignedBigInteger('location_id')->nullable()->index();
            $t->string('supplier_code', 60); $t->string('name', 180); $t->string('contact_person', 160)->nullable();
            $t->string('phone', 60)->nullable(); $t->string('email', 160)->nullable(); $t->text('address')->nullable();
            $t->string('tax_no', 80)->nullable(); $t->decimal('credit_limit', 22, 4)->default(0); $t->unsignedInteger('credit_days')->default(0);
            $t->boolean('is_active')->default(true)->index(); $t->timestamps();
            $t->unique(['business_id','supplier_code'], 'restnew_supplier_business_code_uq');
            $t->index(['business_id','location_id','name'], 'restnew_supplier_scope_name_idx');
        });
        if (! Schema::hasTable('restnew_goods_receipts')) Schema::create('restnew_goods_receipts', function (Blueprint $t) {
            $t->bigIncrements('id'); $t->unsignedBigInteger('business_id')->index(); $t->unsignedBigInteger('location_id')->nullable()->index();
            $t->unsignedBigInteger('supplier_id')->nullable()->index(); $t->string('receipt_no', 80); $t->string('supplier_invoice_no', 100)->nullable();
            $t->date('received_date')->index(); $t->string('status', 30)->default('draft')->index(); $t->decimal('subtotal',22,4)->default(0);
            $t->decimal('discount_total',22,4)->default(0); $t->decimal('tax_total',22,4)->default(0); $t->decimal('total_amount',22,4)->default(0);
            $t->text('notes')->nullable(); $t->unsignedBigInteger('received_by')->nullable(); $t->dateTime('posted_at')->nullable(); $t->unsignedBigInteger('posted_by')->nullable();
            $t->timestamps(); $t->unique(['business_id','receipt_no'], 'restnew_goods_receipt_business_no_uq');
            $t->index(['business_id','location_id','received_date','status'], 'restnew_goods_receipt_scope_date_idx');
        });
        if (! Schema::hasTable('restnew_goods_receipt_lines')) Schema::create('restnew_goods_receipt_lines', function (Blueprint $t) {
            $t->bigIncrements('id'); $t->unsignedBigInteger('business_id')->index(); $t->unsignedBigInteger('goods_receipt_id')->index();
            $t->unsignedBigInteger('ingredient_id')->index(); $t->decimal('quantity',22,4); $t->decimal('unit_cost',22,4)->default(0);
            $t->decimal('discount_amount',22,4)->default(0); $t->decimal('tax_amount',22,4)->default(0); $t->decimal('line_total',22,4)->default(0);
            $t->string('batch_no',100)->nullable(); $t->date('expiry_date')->nullable()->index(); $t->text('notes')->nullable(); $t->timestamps();
            $t->index(['goods_receipt_id','ingredient_id'], 'restnew_goods_receipt_line_item_idx');
        });
        if (! Schema::hasTable('restnew_stock_transfers')) Schema::create('restnew_stock_transfers', function (Blueprint $t) {
            $t->bigIncrements('id'); $t->unsignedBigInteger('business_id')->index(); $t->unsignedBigInteger('from_location_id')->index();
            $t->unsignedBigInteger('to_location_id')->index(); $t->string('transfer_no',80); $t->date('transfer_date')->index();
            $t->string('status',30)->default('draft')->index(); $t->text('notes')->nullable(); $t->unsignedBigInteger('created_by')->nullable();
            $t->dateTime('dispatched_at')->nullable(); $t->unsignedBigInteger('dispatched_by')->nullable(); $t->dateTime('received_at')->nullable(); $t->unsignedBigInteger('received_by')->nullable();
            $t->timestamps(); $t->unique(['business_id','transfer_no'], 'restnew_stock_transfer_business_no_uq');
            $t->index(['business_id','from_location_id','to_location_id','status'], 'restnew_stock_transfer_scope_idx');
        });
        if (! Schema::hasTable('restnew_stock_transfer_lines')) Schema::create('restnew_stock_transfer_lines', function (Blueprint $t) {
            $t->bigIncrements('id'); $t->unsignedBigInteger('business_id')->index(); $t->unsignedBigInteger('stock_transfer_id')->index();
            $t->unsignedBigInteger('ingredient_id')->index(); $t->decimal('requested_qty',22,4); $t->decimal('dispatched_qty',22,4)->default(0);
            $t->decimal('received_qty',22,4)->default(0); $t->decimal('unit_cost',22,4)->default(0); $t->text('notes')->nullable(); $t->timestamps();
            $t->unique(['stock_transfer_id','ingredient_id'], 'restnew_stock_transfer_ingredient_uq');
        });
        if (! Schema::hasTable('restnew_stocktakes')) Schema::create('restnew_stocktakes', function (Blueprint $t) {
            $t->bigIncrements('id'); $t->unsignedBigInteger('business_id')->index(); $t->unsignedBigInteger('location_id')->nullable()->index();
            $t->string('stocktake_no',80); $t->date('stocktake_date')->index(); $t->string('status',30)->default('draft')->index();
            $t->text('notes')->nullable(); $t->unsignedBigInteger('counted_by')->nullable(); $t->dateTime('posted_at')->nullable(); $t->unsignedBigInteger('posted_by')->nullable();
            $t->timestamps(); $t->unique(['business_id','stocktake_no'], 'restnew_stocktake_business_no_uq');
            $t->index(['business_id','location_id','stocktake_date','status'], 'restnew_stocktake_scope_date_idx');
        });
        if (! Schema::hasTable('restnew_stocktake_lines')) Schema::create('restnew_stocktake_lines', function (Blueprint $t) {
            $t->bigIncrements('id'); $t->unsignedBigInteger('business_id')->index(); $t->unsignedBigInteger('stocktake_id')->index();
            $t->unsignedBigInteger('ingredient_id')->index(); $t->decimal('system_qty',22,4)->default(0); $t->decimal('counted_qty',22,4)->default(0);
            $t->decimal('variance_qty',22,4)->default(0); $t->decimal('unit_cost',22,4)->default(0); $t->decimal('variance_value',22,4)->default(0); $t->text('notes')->nullable(); $t->timestamps();
            $t->unique(['stocktake_id','ingredient_id'], 'restnew_stocktake_ingredient_uq');
        });
        if (! Schema::hasTable('restnew_wastages')) Schema::create('restnew_wastages', function (Blueprint $t) {
            $t->bigIncrements('id'); $t->unsignedBigInteger('business_id')->index(); $t->unsignedBigInteger('location_id')->nullable()->index();
            $t->string('wastage_no',80); $t->date('wastage_date')->index(); $t->string('reason_code',50)->index(); $t->string('status',30)->default('posted')->index();
            $t->text('notes')->nullable(); $t->unsignedBigInteger('reported_by')->nullable(); $t->unsignedBigInteger('approved_by')->nullable(); $t->dateTime('approved_at')->nullable();
            $t->timestamps(); $t->unique(['business_id','wastage_no'], 'restnew_wastage_business_no_uq');
            $t->index(['business_id','location_id','wastage_date','reason_code'], 'restnew_wastage_scope_date_idx');
        });
        if (! Schema::hasTable('restnew_wastage_lines')) Schema::create('restnew_wastage_lines', function (Blueprint $t) {
            $t->bigIncrements('id'); $t->unsignedBigInteger('business_id')->index(); $t->unsignedBigInteger('wastage_id')->index();
            $t->unsignedBigInteger('ingredient_id')->index(); $t->decimal('quantity',22,4); $t->decimal('unit_cost',22,4)->default(0); $t->decimal('value',22,4)->default(0);
            $t->string('batch_no',100)->nullable(); $t->text('notes')->nullable(); $t->timestamps();
            $t->index(['wastage_id','ingredient_id'], 'restnew_wastage_line_item_idx');
        });
        if (! Schema::hasTable('restnew_delivery_zones')) Schema::create('restnew_delivery_zones', function (Blueprint $t) {
            $t->bigIncrements('id'); $t->unsignedBigInteger('business_id')->index(); $t->unsignedBigInteger('location_id')->nullable()->index();
            $t->string('zone_code',50); $t->string('name',120); $t->decimal('minimum_order',22,4)->default(0); $t->decimal('delivery_fee',22,4)->default(0);
            $t->unsignedInteger('estimated_minutes')->default(30); $t->boolean('is_active')->default(true)->index(); $t->timestamps();
            $t->unique(['business_id','location_id','zone_code'], 'restnew_delivery_zone_scope_code_uq');
        });
        if (! Schema::hasTable('restnew_delivery_dispatches')) Schema::create('restnew_delivery_dispatches', function (Blueprint $t) {
            $t->bigIncrements('id'); $t->unsignedBigInteger('business_id')->index(); $t->unsignedBigInteger('location_id')->nullable()->index();
            $t->unsignedBigInteger('order_id')->index(); $t->unsignedBigInteger('delivery_zone_id')->nullable()->index(); $t->unsignedBigInteger('driver_user_id')->nullable()->index();
            $t->string('dispatch_no',80); $t->string('status',30)->default('waiting')->index(); $t->text('delivery_address'); $t->string('customer_phone',60)->nullable();
            $t->text('instructions')->nullable(); $t->dateTime('assigned_at')->nullable(); $t->dateTime('dispatched_at')->nullable(); $t->dateTime('delivered_at')->nullable();
            $t->decimal('cash_to_collect',22,4)->default(0); $t->decimal('cash_collected',22,4)->default(0); $t->unsignedBigInteger('created_by')->nullable(); $t->timestamps();
            $t->unique(['business_id','dispatch_no'], 'restnew_delivery_dispatch_business_no_uq');
            $t->unique(['order_id'], 'restnew_delivery_dispatch_order_uq');
            $t->index(['business_id','location_id','status','created_at'], 'restnew_delivery_dispatch_queue_idx');
        });
        if (! Schema::hasTable('restnew_discount_rules')) Schema::create('restnew_discount_rules', function (Blueprint $t) {
            $t->bigIncrements('id'); $t->unsignedBigInteger('business_id')->index(); $t->unsignedBigInteger('location_id')->nullable()->index();
            $t->string('rule_code',50); $t->string('name',140); $t->string('discount_type',20)->default('percentage'); $t->decimal('discount_value',22,4)->default(0);
            $t->decimal('maximum_discount',22,4)->nullable(); $t->decimal('minimum_order',22,4)->default(0); $t->date('starts_on')->nullable(); $t->date('ends_on')->nullable();
            $t->time('starts_at')->nullable(); $t->time('ends_at')->nullable(); $t->json('days_json')->nullable(); $t->string('order_type',30)->nullable();
            $t->boolean('requires_manager')->default(false); $t->boolean('is_active')->default(true)->index(); $t->timestamps();
            $t->unique(['business_id','location_id','rule_code'], 'restnew_discount_rule_scope_code_uq');
        });
        if (! Schema::hasTable('restnew_discount_usages')) Schema::create('restnew_discount_usages', function (Blueprint $t) {
            $t->bigIncrements('id'); $t->unsignedBigInteger('business_id')->index(); $t->unsignedBigInteger('order_id')->index(); $t->unsignedBigInteger('discount_rule_id')->nullable()->index();
            $t->string('rule_code',50)->nullable(); $t->string('rule_name',140)->nullable(); $t->decimal('discount_amount',22,4)->default(0); $t->string('reason',255)->nullable();
            $t->unsignedBigInteger('applied_by')->nullable(); $t->unsignedBigInteger('approved_by')->nullable(); $t->timestamps();
            $t->index(['business_id','created_at'], 'restnew_discount_usage_business_date_idx');
        });
        if (! Schema::hasTable('restnew_order_adjustments')) Schema::create('restnew_order_adjustments', function (Blueprint $t) {
            $t->bigIncrements('id'); $t->unsignedBigInteger('business_id')->index(); $t->unsignedBigInteger('order_id')->index(); $t->unsignedBigInteger('order_item_id')->nullable()->index();
            $t->string('adjustment_type',40)->index(); $t->decimal('amount',22,4)->default(0); $t->text('reason'); $t->json('before_json')->nullable(); $t->json('after_json')->nullable();
            $t->unsignedBigInteger('requested_by')->nullable(); $t->unsignedBigInteger('approved_by')->nullable(); $t->dateTime('approved_at')->nullable(); $t->timestamps();
            $t->index(['business_id','adjustment_type','created_at'], 'restnew_order_adjustment_type_date_idx');
        });
        if (! Schema::hasTable('restnew_manager_approvals')) Schema::create('restnew_manager_approvals', function (Blueprint $t) {
            $t->bigIncrements('id'); $t->unsignedBigInteger('business_id')->index(); $t->unsignedBigInteger('location_id')->nullable()->index();
            $t->string('approval_type',50)->index(); $t->string('entity_type',80); $t->unsignedBigInteger('entity_id')->nullable(); $t->string('status',30)->default('approved')->index();
            $t->text('reason')->nullable(); $t->json('payload_json')->nullable(); $t->unsignedBigInteger('requested_by')->nullable(); $t->unsignedBigInteger('approved_by')->nullable();
            $t->dateTime('approved_at')->nullable(); $t->timestamps(); $t->index(['business_id','location_id','approval_type','created_at'], 'restnew_manager_approval_scope_idx');
        });
    }

    public function down(): void
    {
        foreach (['restnew_manager_approvals','restnew_order_adjustments','restnew_discount_usages','restnew_discount_rules','restnew_delivery_dispatches','restnew_delivery_zones','restnew_wastage_lines','restnew_wastages','restnew_stocktake_lines','restnew_stocktakes','restnew_stock_transfer_lines','restnew_stock_transfers','restnew_goods_receipt_lines','restnew_goods_receipts','restnew_suppliers'] as $table) Schema::dropIfExists($table);
    }
};
