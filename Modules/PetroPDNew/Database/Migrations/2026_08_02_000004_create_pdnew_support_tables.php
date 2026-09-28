<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $path = dirname(__DIR__) . '/Sql/04_CREATE_INTEGRATION_AND_SUPPORT_TABLES.sql';
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
            'pdnew_saved_report_filters',
            'pdnew_documents',
            'pdnew_audit_logs',
            'pdnew_print_logs',
            'pdnew_notification_logs',
            'pdnew_notification_templates',
            'pdnew_integration_logs',
            'pdnew_integration_outbox'
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
