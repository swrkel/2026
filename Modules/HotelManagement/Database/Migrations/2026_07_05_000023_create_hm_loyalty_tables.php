<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $sql = file_get_contents(__DIR__.'/../../Docs/HOTELMGT_023_SQL.sql');
        DB::unprepared($sql);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS hm_loyalty_point_ledger');
        DB::statement('DROP TABLE IF EXISTS hm_loyalty_members');
        DB::statement('DROP TABLE IF EXISTS hm_loyalty_tiers');
    }
};
