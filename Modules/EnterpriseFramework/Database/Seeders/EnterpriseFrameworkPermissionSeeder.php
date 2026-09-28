<?php

namespace Modules\EnterpriseFramework\Database\Seeders;

use Illuminate\Database\Seeder;

class EnterpriseFrameworkPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = config('enterpriseframework.permissions', []);

        // Intentionally non-destructive placeholder. Wire into your app's permission model if required.
        foreach ($permissions as $permission) {
            // Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
