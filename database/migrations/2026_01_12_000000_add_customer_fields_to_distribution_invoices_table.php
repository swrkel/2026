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
            $table->string('customer_name')->nullable()->after('customer_id');
            $table->string('customer_address')->nullable()->after('customer_name');
            $table->string('customer_contact')->nullable()->after('customer_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('distribution_invoices', function (Blueprint $table) {
            $table->dropColumn(['customer_name', 'customer_address', 'customer_contact']);
        });
    }
};
