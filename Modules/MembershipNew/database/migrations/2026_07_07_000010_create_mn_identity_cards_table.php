<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Use SQL/MembershipNew/MembershipNew_MEMNEW_002.sql for exact tenant-safe table creation.
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS `mn_identity_cards`');
    }
};
