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
            $table->unsignedBigInteger('added_by')->nullable()->after('sales_order_id');
            $table->unsignedBigInteger('updated_by')->nullable()->after('added_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('distribution_invoices', function (Blueprint $table) {
            $table->dropColumn(['added_by', 'updated_by']);
        });
    }
};
