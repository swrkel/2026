<?php

namespace Modules\Ran\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RanPermissionSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('permissions')) {
            $this->command?->warn('Ran permissions were not seeded because the permissions table is unavailable.');
            return;
        }

        $definitions = require module_path('Ran', 'Config/permissions.php');

        foreach ($definitions as $definition) {
            $name = $definition['permission'] ?? $definition['name'] ?? null;
            if (! is_string($name) || $name === '') {
                continue;
            }

            $exists = DB::table('permissions')
                ->where('name', $name)
                ->where('guard_name', 'web')
                ->exists();

            if (! $exists) {
                DB::table('permissions')->insert([
                    'name' => $name,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
