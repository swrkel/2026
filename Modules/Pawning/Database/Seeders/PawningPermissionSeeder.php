<?php

namespace Modules\Pawning\Database\Seeders;

use Illuminate\Database\Seeder;

class PawningPermissionSeeder extends Seeder
{
    public function run()
    {
        // Permissions are seeded through the existing ERP permission workflow.
        // Suggested permission keys: pawning.view, pawning.create, pawning.update, pawning.redeem, pawning.auction, pawning.reports, pawning.settings.
    }
}
