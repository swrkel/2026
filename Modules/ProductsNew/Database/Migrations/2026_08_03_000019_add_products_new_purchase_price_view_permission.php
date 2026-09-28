<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('permissions')
            && Schema::hasColumn('permissions', 'name')
            && Schema::hasColumn('permissions', 'guard_name')) {
            $exists = DB::table('permissions')
                ->where('name', 'products_new.purchase_price.view')
                ->where('guard_name', 'web')
                ->exists();

            if (! $exists) {
                $payload = [
                    'name' => 'products_new.purchase_price.view',
                    'guard_name' => 'web',
                ];

                if (Schema::hasColumn('permissions', 'created_at')) {
                    $payload['created_at'] = now();
                }
                if (Schema::hasColumn('permissions', 'updated_at')) {
                    $payload['updated_at'] = now();
                }

                DB::table('permissions')->insert($payload);
            }
        }

        if (Schema::hasTable('products_new_permissions')
            && Schema::hasColumn('products_new_permissions', 'name')) {
            $exists = DB::table('products_new_permissions')
                ->where('name', 'products_new.purchase_price.view')
                ->exists();

            if (! $exists) {
                $payload = ['name' => 'products_new.purchase_price.view'];

                if (Schema::hasColumn('products_new_permissions', 'display_name')) {
                    $payload['display_name'] = 'View Purchase Price';
                }
                if (Schema::hasColumn('products_new_permissions', 'module')) {
                    $payload['module'] = 'Products New';
                }
                if (Schema::hasColumn('products_new_permissions', 'created_at')) {
                    $payload['created_at'] = now();
                }
                if (Schema::hasColumn('products_new_permissions', 'updated_at')) {
                    $payload['updated_at'] = now();
                }

                DB::table('products_new_permissions')->insert($payload);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products_new_permissions')) {
            DB::table('products_new_permissions')
                ->where('name', 'products_new.purchase_price.view')
                ->delete();
        }

        if (Schema::hasTable('permissions')) {
            DB::table('permissions')
                ->where('name', 'products_new.purchase_price.view')
                ->where('guard_name', 'web')
                ->delete();
        }
    }
};
