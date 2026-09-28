<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('distribution_invoices', function (Blueprint $table) {
            $table->date('delivery_date')->nullable()->after('date');
            $table->text('invoice_note')->nullable()->after('loading_sheet_no');
            $table->text('shipping_note')->nullable()->after('invoice_note');
            $table->text('shipping_details')->nullable()->after('shipping_note');
            $table->enum('shipping_status', ['ordered', 'packed', 'shipped', 'delivered', 'cancelled'])->default('ordered')->after('shipping_details');
            $table->string('status', 30)->default('active')->after('shipping_status');
            $table->unsignedBigInteger('sales_order_id')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('distribution_invoices', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_date',
                'invoice_note',
                'shipping_note',
                'shipping_details',
                'shipping_status',
                'status',
                'sales_order_id',
            ]);
        });
    }
};
