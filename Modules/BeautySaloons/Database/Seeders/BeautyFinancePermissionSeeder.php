<?php
namespace Modules\BeautySaloons\Database\Seeders;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class BeautyFinancePermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['beauty.finance.view','beauty.finance.mapping','beauty.finance.posting','beauty.finance.reconcile'] as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
