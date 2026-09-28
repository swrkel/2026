<?php

namespace Modules\BankingTesterUI\Database\Seeders;

use Illuminate\Database\Seeder;

class BankingTesterUIPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Permission systems differ between tenants. This seeder is intentionally safe/no-op.
        // Add banking.tester_ui.view to your role/permission UI if your tenant requires explicit permission.
    }
}
