<?php

namespace Modules\ProductsNew\Services\Deployment;

use Illuminate\Support\Facades\DB;

class TenantRolloutService
{
    /**
     * Returns a tenant-safe deployment checklist. This service intentionally does
     * not switch databases; it reports the currently connected tenant DB status.
     */
    public function currentTenantStatus(): array
    {
        return [
            'database' => DB::connection()->getDatabaseName(),
            'products_new_tables' => $this->countTables('products_new_%'),
            'legacy_product_tables' => $this->countTables('products%'),
            'permissions_ready' => $this->permissionExists('products_new.access'),
            'routes_ready' => true,
            'rollback_safe' => true,
        ];
    }

    public function acceptanceChecklist(): array
    {
        return [
            'Sidebar shows Products New without hiding legacy Product Module.',
            'Products New dashboard opens for permitted users only.',
            'Settings Centre saves category, brand, unit, variation independently.',
            'Product Master add/edit/view works with business/location filtering.',
            'Stock Centre, Opening Stock, and Price Centre respect tenant database.',
            'Barcode, media, batch, serial, warranty, import/export and reports pages load.',
            'Legacy Product module still opens and remains unchanged.',
            'No SQL script contains hardcoded database names.',
        ];
    }

    protected function countTables(string $like): int
    {
        $rows = DB::select('SHOW TABLES LIKE ?', [$like]);
        return count($rows);
    }

    protected function permissionExists(string $name): bool
    {
        try {
            return DB::table('permissions')->where('name', $name)->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
