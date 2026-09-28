<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distribution_invoices', function (Blueprint $table) {
            $table->decimal('payment_cash', 22, 4)->default(0)->after('grand_total');
            $table->decimal('payment_card', 22, 4)->default(0)->after('payment_cash');
            $table->decimal('payment_credit', 22, 4)->default(0)->after('payment_card');
            $table->decimal('payment_cheque', 22, 4)->default(0)->after('payment_credit');
            $table->decimal('payment_total', 22, 4)->default(0)->after('payment_cheque');
        });
    }

    public function down(): void
    {
        Schema::table('distribution_invoices', function (Blueprint $table) {
            $table->dropColumn([
                'payment_cash',
                'payment_card',
                'payment_credit',
                'payment_cheque',
                'payment_total',
            ]);
        });
    }
};

