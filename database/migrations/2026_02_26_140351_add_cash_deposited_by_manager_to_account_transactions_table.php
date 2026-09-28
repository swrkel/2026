<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('account_transactions')) {
            return;
        }

        if (!Schema::hasColumn('account_transactions', 'cash_deposited_by_manager')) {
            Schema::table('account_transactions', function (Blueprint $table) {
                $table->decimal('cash_deposited_by_manager', 20, 4)
                    ->default(0)
                    ->after('amount');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('account_transactions')) {
            return;
        }

        if (Schema::hasColumn('account_transactions', 'cash_deposited_by_manager')) {
            Schema::table('account_transactions', function (Blueprint $table) {
                $table->dropColumn('cash_deposited_by_manager');
            });
        }
    }
};
