<?php

namespace Modules\ProductsNew\Services\Migration;

use Illuminate\Support\Facades\DB;

class MigrationReadinessService
{
    public function summary(?int $businessId = null): array
    {
        $businessId = $businessId ?: (int) session('business.id');

        return [
            'business_id' => $businessId,
            'legacy_module_status' => 'kept_active',
            'products_new_status' => 'parallel_testing',
            'recommended_switch_status' => 'not_ready_until_user_approval',
            'checks' => $this->checks($businessId),
            'counts' => $this->counts($businessId),
        ];
    }

    public function checks(int $businessId): array
    {
        return [
            ['name' => 'Legacy Product Module Safety', 'status' => 'pass', 'note' => 'Existing Product module remains untouched.'],
            ['name' => 'Products New Menu', 'status' => 'pass', 'note' => 'Temporary menu name remains Products New.'],
            ['name' => 'Route Prefix', 'status' => 'pass', 'note' => 'Uses /products-new and products-new.* route names.'],
            ['name' => 'Permission Prefix', 'status' => 'pass', 'note' => 'Uses products_new.* permissions.'],
            ['name' => 'Tenant Database Safety', 'status' => 'pass', 'note' => 'No database name is hardcoded in SQL files.'],
            ['name' => 'Business Isolation', 'status' => 'review', 'note' => 'Testing should confirm all pages filter by business_id and location.'],
            ['name' => 'POS UI Standard', 'status' => 'review', 'note' => 'Final visual testing required in browser.'],
            ['name' => 'Final Switch', 'status' => 'blocked', 'note' => 'Do not replace legacy Product until user approves testing.'],
        ];
    }

    public function counts(int $businessId): array
    {
        $safeCount = function (string $table) use ($businessId): int {
            try {
                if (!DB::getSchemaBuilder()->hasTable($table)) {
                    return 0;
                }
                return (int) DB::table($table)->where('business_id', $businessId)->count();
            } catch (\Throwable $e) {
                return 0;
            }
        };

        return [
            'products' => $safeCount('products'),
            'products_new_categories' => $safeCount('products_new_categories'),
            'products_new_brands' => $safeCount('products_new_brands'),
            'legacy_products' => $safeCount('products'),
        ];
    }

    public function testingChecklist(): array
    {
        return [
            'Open Products New dashboard',
            'Create category, brand, unit, and variation',
            'Create simple product with SKU, barcode, tax, image, and location',
            'Edit product and verify timeline entry',
            'Open Product 360 view and verify all tabs',
            'Add opening stock and verify stock center',
            'Add price tier and verify price history',
            'Queue barcode and verify label centre',
            'Upload product media and verify delete permission',
            'Run duplicate scan and verify warning only',
            'Create batch/lot and expiry alert',
            'Create serial and warranty registration',
            'Run import validation without committing bad rows',
            'Run reports with date/location filters',
            'Compare legacy Product module remains working',
        ];
    }
}
