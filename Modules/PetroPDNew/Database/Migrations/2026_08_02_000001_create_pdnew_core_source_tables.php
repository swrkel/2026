<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $path = dirname(__DIR__) . '/Sql/01_CREATE_CORE_AND_SOURCE_TABLES.sql';
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
            'pdnew_source_snapshots',
            'pdnew_source_imports',
            'pdnew_operator_mappings',
            'pdnew_number_sequences',
            'pdnew_module_settings'
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
