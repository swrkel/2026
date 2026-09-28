<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $path = dirname(__DIR__) . '/Sql/03_CREATE_WORKFLOW_AND_DAY_END_TABLES.sql';
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
            'pdnew_posting_lines',
            'pdnew_posting_batches',
            'pdnew_day_end_settlements',
            'pdnew_day_ends',
            'pdnew_reconciliation_issues',
            'pdnew_settlement_status_histories',
            'pdnew_settlement_approvals'
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
