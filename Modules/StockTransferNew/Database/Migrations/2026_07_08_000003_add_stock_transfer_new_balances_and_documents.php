<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('stnew_stock_balances')) {
            Schema::create('stnew_stock_balances', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('business_location_id')->nullable()->index();
                $table->unsignedBigInteger('store_id')->nullable()->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedBigInteger('variation_id')->nullable()->index();
                $table->decimal('qty_on_hand', 22, 4)->default(0);
                $table->decimal('qty_in_transit', 22, 4)->default(0);
                $table->decimal('last_unit_cost', 22, 4)->default(0);
                $table->timestamp('last_movement_at')->nullable();
                $table->timestamps();
                $table->unique(['business_id','business_location_id','store_id','product_id','variation_id'], 'stnew_balances_unique');
            });
        }

        Schema::table('stnew_stock_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('stnew_stock_transfers', 'dispatch_note_no')) {
                $table->string('dispatch_note_no')->nullable()->after('transfer_no');
            }
            if (!Schema::hasColumn('stnew_stock_transfers', 'receive_note_no')) {
                $table->string('receive_note_no')->nullable()->after('dispatch_note_no');
            }
            if (!Schema::hasColumn('stnew_stock_transfers', 'vehicle_no')) {
                $table->string('vehicle_no')->nullable()->after('remarks');
            }
            if (!Schema::hasColumn('stnew_stock_transfers', 'driver_name')) {
                $table->string('driver_name')->nullable()->after('vehicle_no');
            }
            if (!Schema::hasColumn('stnew_stock_transfers', 'driver_mobile')) {
                $table->string('driver_mobile')->nullable()->after('driver_name');
            }
        });

        Schema::table('stnew_stock_transfer_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('stnew_stock_transfer_lines', 'batch_no')) {
                $table->string('batch_no')->nullable()->after('variation_id');
            }
            if (!Schema::hasColumn('stnew_stock_transfer_lines', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('batch_no');
            }
            if (!Schema::hasColumn('stnew_stock_transfer_lines', 'short_qty')) {
                $table->decimal('short_qty', 22, 4)->default(0)->after('qty_received');
            }
            if (!Schema::hasColumn('stnew_stock_transfer_lines', 'excess_qty')) {
                $table->decimal('excess_qty', 22, 4)->default(0)->after('short_qty');
            }
        });
    }

    public function down()
    {
        Schema::dropIfExists('stnew_stock_balances');
    }
};
