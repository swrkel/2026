<?php

use Illuminate\Database\Migrations\Migration;
use Modules\StockTakingNew\Database\Seeders\StockTakingNewPermissionSeeder;

return new class extends Migration
{
    public function up(): void
    {
        (new StockTakingNewPermissionSeeder())->run();
    }

    public function down(): void
    {
        // Permissions are deliberately retained because roles/users may reference them.
    }
};
