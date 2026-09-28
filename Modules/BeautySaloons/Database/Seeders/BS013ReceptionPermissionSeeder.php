<?php

namespace Modules\BeautySaloons\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class BS013ReceptionPermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'beautysaloons.reception.view',
            'beautysaloons.reception.create',
            'beautysaloons.reception.update',
            'beautysaloons.reception.check_in',
            'beautysaloons.reception.start_service',
            'beautysaloons.reception.complete',
            'beautysaloons.reception.dashboard',
            'beautysaloons.reception.report',
        ] as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
