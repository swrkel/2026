<?php

namespace Modules\Product\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = config('product_permissions.permissions', []);
        $now = now();

        foreach ($permissions as $name => $label) {
            if (!DB::table('permissions')->where('name', $name)->exists()) {
                DB::table('permissions')->insert([
                    'name' => $name,
                    'guard_name' => 'web',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
