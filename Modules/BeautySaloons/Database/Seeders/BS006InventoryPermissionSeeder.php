<?php
namespace Modules\BeautySaloons\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BS006InventoryPermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'beauty_saloons.inventory.view', 'beauty_saloons.inventory.create', 'beauty_saloons.inventory.update', 'beauty_saloons.inventory.delete',
            'beauty_saloons.stock.view', 'beauty_saloons.stock.adjust', 'beauty_saloons.retail_sales.view', 'beauty_saloons.retail_sales.create',
            'beauty_saloons.inventory.reports.view'
        ] as $permission) {
            DB::table('permissions')->updateOrInsert(['name' => $permission], ['guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        }
    }
}
