<?php

namespace Modules\BeautySaloons\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BS012SchedulerPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'beauty_saloons.scheduler.view',
            'beauty_saloons.scheduler.create',
            'beauty_saloons.scheduler.update',
            'beauty_saloons.scheduler.cancel',
            'beauty_saloons.waitlist.view',
            'beauty_saloons.waitlist.create',
            'beauty_saloons.waitlist.convert',
            'beauty_saloons.recurring_appointments.view',
            'beauty_saloons.recurring_appointments.create',
        ];

        foreach ($permissions as $permission) {
            if (DB::getSchemaBuilder()->hasTable('permissions')) {
                DB::table('permissions')->updateOrInsert(
                    ['name' => $permission, 'guard_name' => 'web'],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }
}
