<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $table = 'san_stock_adjustment_account_mappings';
        if (! Schema::hasTable($table)) {
            return;
        }

        if (! Schema::hasColumn($table, 'increase_account_id')) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->unsignedBigInteger('increase_account_id')
                    ->nullable()
                    ->after('account_to_link_id')
                    ->index('san_mapping_increase_account_idx');
            });
        }

        if (! Schema::hasColumn($table, 'decrease_account_id')) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->unsignedBigInteger('decrease_account_id')
                    ->nullable()
                    ->after('increase_account_id')
                    ->index('san_mapping_decrease_account_idx');
            });
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive. The two direction-account columns may
        // contain live accounting configuration and must not be dropped on a
        // rollback of application code.
    }
};
