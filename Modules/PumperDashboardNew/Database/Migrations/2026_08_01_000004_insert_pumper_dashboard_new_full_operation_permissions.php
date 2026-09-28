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
        // Deliberately retained because existing roles may reference these permissions.
    }
};
