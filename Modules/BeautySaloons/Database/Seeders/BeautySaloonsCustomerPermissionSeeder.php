<?php

namespace Modules\BeautySaloons\Database\Seeders;

use Illuminate\Database\Seeder;

class BeautySaloonsCustomerPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'beauty_saloons.customers.view',
            'beauty_saloons.customers.create',
            'beauty_saloons.customers.update',
            'beauty_saloons.customers.delete',
            'beauty_saloons.customers.visit_notes',
            'beauty_saloons.customers.reports',
        ];

        foreach ($permissions as $permission) {
            // Insert into your permissions table using the ERP's existing permission utility if available.
        }
    }
}
