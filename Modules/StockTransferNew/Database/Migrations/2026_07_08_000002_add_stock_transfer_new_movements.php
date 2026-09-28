<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('stnew_stock_transfer_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('stnew_stock_transfer_lines', 'short_qty')) {
                $table->decimal('short_qty', 22, 4)->default(0)->after('qty_received');
            }
            if (!Schema::hasColumn('stnew_stock_transfer_lines', 'excess_qty')) {
                $table->decimal('excess_qty', 22, 4)->default(0)->after('short_qty');
            }
        });

        if (!Schema::hasTable('stnew_stock_movements')) {
            Schema::create('stnew_stock_movements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('stock_transfer_id')->index();
                $table->unsignedBigInteger('stock_transfer_line_id')->nullable()->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedBigInteger('variation_id')->nullable()->index();
                $table->unsignedBigInteger('from_location_id')->nullable()->index();
                $table->unsignedBigInteger('to_location_id')->nullable()->index();
                $table->unsignedBigInteger('from_store_id')->nullable()->index();
                $table->unsignedBigInteger('to_store_id')->nullable()->index();
                $table->string('movement_type', 30)->index();
                $table->decimal('quantity', 22, 4)->default(0);
                $table->decimal('unit_cost', 22, 4)->default(0);
                $table->decimal('total_cost', 22, 4)->default(0);
                $table->string('reference_no')->nullable()->index();
                $table->timestamp('movement_date')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('stnew_stock_movements');
    }
};
