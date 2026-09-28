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
            $table->text('sales_order_note')->nullable()->after('shipping_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('distribution_invoices', function (Blueprint $table) {
            $table->dropColumn('sales_order_note');
        });
    }
};
