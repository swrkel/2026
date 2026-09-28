<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $path = dirname(__DIR__) . '/Sql/02_CREATE_SETTLEMENT_TABLES.sql';
        $sql = is_file($path) ? file_get_contents($path) : '';
        $sql = preg_replace('/^\s*--.*$/m', '', (string) $sql);
        $statements = preg_split('/;\s*(?:\r?\n|$)/', (string) $sql) ?: [];

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement === '' || str_starts_with($statement, 'SET ')) continue;
            DB::unprepared($statement);
        }
    }

    public function down(): void
    {
        foreach ([
            'pdnew_settlement_adjustments',
            'pdnew_settlement_commissions',
            'pdnew_settlement_recoveries',
            'pdnew_settlement_ledger_entries',
            'pdnew_settlement_collections',
            'pdnew_settlement_day_entries',
            'pdnew_settlement_unload_stock_lines',
            'pdnew_settlement_unload_stocks',
            'pdnew_settlement_other_sale_lines',
            'pdnew_settlement_other_sales',
            'pdnew_settlement_credit_sale_lines',
            'pdnew_settlement_credit_sales',
            'pdnew_settlement_payment_details',
            'pdnew_settlement_payments',
            'pdnew_settlement_meter_sales',
            'pdnew_settlement_pumps',
            'pdnew_settlement_sources',
            'pdnew_settlements'
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
