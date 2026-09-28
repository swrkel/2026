<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sql = file_get_contents(module_path('DistributionNew', 'Database/Sql/DISNEW_015_advanced_analytics.sql'));
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            if ($statement !== '') { DB::statement($statement); }
        }
    }

    public function down(): void
    {
        $sql = file_get_contents(module_path('DistributionNew', 'Database/Sql/DISNEW_015_rollback.sql'));
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            if ($statement !== '') { DB::statement($statement); }
        }
    }
};
