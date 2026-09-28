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
        if (! Schema::hasTable('settlement_credit_sale_payments') || Schema::hasColumn('settlement_credit_sale_payments', 'bill_number')) {
            return;
        }

        Schema::table('settlement_credit_sale_payments', function (Blueprint $table) {
            $table->string('bill_number', 255)->nullable()->after('collection_form_no');
            $table->index('bill_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('settlement_credit_sale_payments') || ! Schema::hasColumn('settlement_credit_sale_payments', 'bill_number')) {
            return;
        }

        Schema::table('settlement_credit_sale_payments', function (Blueprint $table) {
            $table->dropIndex('settlement_credit_sale_payments_bill_number_index');
            $table->dropColumn('bill_number');
        });
    }
};
