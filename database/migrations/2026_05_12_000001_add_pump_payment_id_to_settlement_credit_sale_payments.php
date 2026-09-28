<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settlement_credit_sale_payments') || Schema::hasColumn('settlement_credit_sale_payments', 'pump_payment_id')) {
            return;
        }

        Schema::table('settlement_credit_sale_payments', function (Blueprint $table) {
            $table->unsignedInteger('pump_payment_id')->nullable()->after('transaction_id');
            $table->index('pump_payment_id', 'idx_scsp_pump_payment_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('settlement_credit_sale_payments') || ! Schema::hasColumn('settlement_credit_sale_payments', 'pump_payment_id')) {
            return;
        }

        Schema::table('settlement_credit_sale_payments', function (Blueprint $table) {
            $table->dropIndex('idx_scsp_pump_payment_id');
            $table->dropColumn('pump_payment_id');
        });
    }
};
