<?php

namespace Modules\BeautySaloons\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class BS016NotificationPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = require module_path('BeautySaloons', 'Permissions/BS016_NotificationPermissions.php');

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
