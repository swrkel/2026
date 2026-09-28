<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mpcs_f10_headers') && !Schema::hasColumn('mpcs_f10_headers', 'total_amount')) {
            Schema::table('mpcs_f10_headers', function (Blueprint $table) {
                $table->decimal('total_amount', 22, 4)->default(0)->after('manager_id');
                $table->decimal('cash_amount', 22, 4)->default(0)->after('total_amount');
                $table->decimal('bank_amount', 22, 4)->default(0)->after('cash_amount');
                $table->decimal('cheque_amount', 22, 4)->default(0)->after('bank_amount');
                $table->decimal('card_amount', 22, 4)->default(0)->after('cheque_amount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mpcs_f10_headers')) {
            Schema::table('mpcs_f10_headers', function (Blueprint $table) {
                $table->dropColumn(['total_amount', 'cash_amount', 'bank_amount', 'cheque_amount', 'card_amount']);
            });
        }
    }
};
