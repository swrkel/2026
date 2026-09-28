<?php

namespace Modules\BeautySaloons\Permissions;

use Illuminate\Database\Seeder;

class BS005PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'beauty_saloons.services.view',
            'beauty_saloons.services.create',
            'beauty_saloons.services.update',
            'beauty_saloons.services.delete',
            'beauty_saloons.pricing.view',
            'beauty_saloons.pricing.update',
        ];
        // Insert using your ERP permission utility/table mapping.
    }
}
