<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('restnew_orders')) Schema::table('restnew_orders', function (Blueprint $t) {
            if (! Schema::hasColumn('restnew_orders','reservation_id')) $t->unsignedBigInteger('reservation_id')->nullable()->after('table_id')->index();
            if (! Schema::hasColumn('restnew_orders','delivery_zone_id')) $t->unsignedBigInteger('delivery_zone_id')->nullable()->after('reservation_id')->index();
            if (! Schema::hasColumn('restnew_orders','delivery_address')) $t->text('delivery_address')->nullable()->after('customer_email');
            if (! Schema::hasColumn('restnew_orders','delivery_fee')) $t->decimal('delivery_fee',22,4)->default(0)->after('service_charge_total');
            if (! Schema::hasColumn('restnew_orders','discount_rule_id')) $t->unsignedBigInteger('discount_rule_id')->nullable()->after('delivery_zone_id')->index();
            if (! Schema::hasColumn('restnew_orders','discount_reason')) $t->string('discount_reason',255)->nullable()->after('discount_total');
            if (! Schema::hasColumn('restnew_orders','discount_authorized_by')) $t->unsignedBigInteger('discount_authorized_by')->nullable()->after('discount_reason');
            if (! Schema::hasColumn('restnew_orders','discount_authorized_at')) $t->dateTime('discount_authorized_at')->nullable()->after('discount_authorized_by');
        });
        if (Schema::hasTable('restnew_reservations')) Schema::table('restnew_reservations', function (Blueprint $t) {
            if (! Schema::hasColumn('restnew_reservations','customer_email')) $t->string('customer_email',160)->nullable()->after('customer_phone');
            if (! Schema::hasColumn('restnew_reservations','duration_minutes')) $t->unsignedInteger('duration_minutes')->default(90)->after('reserved_at');
            if (! Schema::hasColumn('restnew_reservations','source')) $t->string('source',30)->default('phone')->after('status');
            if (! Schema::hasColumn('restnew_reservations','deposit_amount')) $t->decimal('deposit_amount',22,4)->default(0)->after('source');
            if (! Schema::hasColumn('restnew_reservations','seated_order_id')) $t->unsignedBigInteger('seated_order_id')->nullable()->after('deposit_amount')->index();
            if (! Schema::hasColumn('restnew_reservations','confirmed_at')) $t->dateTime('confirmed_at')->nullable();
            if (! Schema::hasColumn('restnew_reservations','seated_at')) $t->dateTime('seated_at')->nullable();
            if (! Schema::hasColumn('restnew_reservations','cancelled_at')) $t->dateTime('cancelled_at')->nullable();
        });
        if (Schema::hasTable('restnew_ingredients')) Schema::table('restnew_ingredients', function (Blueprint $t) {
            if (! Schema::hasColumn('restnew_ingredients','supplier_id')) $t->unsignedBigInteger('supplier_id')->nullable()->after('business_id')->index();
            if (! Schema::hasColumn('restnew_ingredients','barcode')) $t->string('barcode',100)->nullable()->after('ingredient_code')->index();
            if (! Schema::hasColumn('restnew_ingredients','purchase_unit')) $t->string('purchase_unit',40)->nullable()->after('unit');
            if (! Schema::hasColumn('restnew_ingredients','purchase_conversion')) $t->decimal('purchase_conversion',22,4)->default(1)->after('purchase_unit');
            if (! Schema::hasColumn('restnew_ingredients','track_expiry')) $t->boolean('track_expiry')->default(false)->after('reorder_level');
        });
        if (Schema::hasTable('restnew_menu_items')) Schema::table('restnew_menu_items', function (Blueprint $t) {
            if (! Schema::hasColumn('restnew_menu_items','is_delivery')) $t->boolean('is_delivery')->default(true)->after('is_takeaway');
        });
        if (Schema::hasTable('restnew_stock_movements')) Schema::table('restnew_stock_movements', function (Blueprint $t) {
            if (! Schema::hasColumn('restnew_stock_movements','batch_no')) $t->string('batch_no',100)->nullable()->after('reference_no');
            if (! Schema::hasColumn('restnew_stock_movements','expiry_date')) $t->date('expiry_date')->nullable()->after('batch_no')->index();
        });
    }
    public function down(): void {}
};
