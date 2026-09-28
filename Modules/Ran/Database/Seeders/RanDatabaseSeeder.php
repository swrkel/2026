<?php

namespace Modules\Ran\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class RanDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Model::unguard();
        $this->call(RanPermissionSeeder::class);
    }
}
