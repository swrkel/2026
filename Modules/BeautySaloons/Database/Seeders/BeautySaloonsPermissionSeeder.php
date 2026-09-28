<?php

namespace Modules\BeautySaloons\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\BeautySaloons\Services\BeautyPermissionService;

class BeautySaloonsPermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (BeautyPermissionService::permissions() as $permission) {
            // Add your ERP permission model creation here.
            // Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
