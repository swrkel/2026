<?php

use Illuminate\Database\Migrations\Migration;
use Modules\PetroPDNew\Database\Seeders\PetroPDNewPermissionSeeder;

return new class extends Migration
{
    public function up(): void
    {
        (new PetroPDNewPermissionSeeder())->run();
    }

    public function down(): void
    {
        // Retained because roles may already reference these permissions.
    }
};
