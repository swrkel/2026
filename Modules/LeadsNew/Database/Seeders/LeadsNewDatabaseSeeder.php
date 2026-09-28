<?php

namespace Modules\LeadsNew\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class LeadsNewDatabaseSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();
        $this->call(LeadsNewPermissionSeeder::class);
    }
}
