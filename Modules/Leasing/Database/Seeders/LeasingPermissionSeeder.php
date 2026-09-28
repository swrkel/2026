<?php

namespace Modules\Leasing\Database\Seeders;

use Illuminate\Database\Seeder;

class LeasingPermissionSeeder extends Seeder
{
    public function run()
    {
        // Permissions are seeded through the existing ERP permission workflow.
        // Suggested permission keys: leasing.view, leasing.create, leasing.update, leasing.redeem, leasing.insurance, leasing.reports, leasing.settings.
    }
}
