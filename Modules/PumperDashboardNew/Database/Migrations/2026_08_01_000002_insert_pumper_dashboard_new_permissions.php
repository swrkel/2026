<?php

use Illuminate\Database\Migrations\Migration;
use Modules\PumperDashboardNew\Database\Seeders\PumperDashboardNewPermissionSeeder;

return new class extends Migration
{
    public function up(): void
    {
        (new PumperDashboardNewPermissionSeeder())->run();
    }

    public function down(): void
    {
        // Permissions are intentionally retained because roles may reference them.
    }
};
