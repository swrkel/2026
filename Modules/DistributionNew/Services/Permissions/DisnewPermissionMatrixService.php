<?php

namespace Modules\DistributionNew\Services\Permissions;

use Modules\DistributionNew\Models\DisnewPermissionAuditLog;

class DisnewPermissionMatrixService
{
    public function matrix(): array
    {
        return [
            'dashboard' => ['distributionnew.view'],
            'sales_orders' => ['distributionnew.sales_order.view','distributionnew.sales_order.create','distributionnew.sales_order.update'],
            'sales_invoices' => ['distributionnew.sales_invoice.view','distributionnew.sales_invoice.create'],
            'loading' => ['distributionnew.loading.view','distributionnew.loading.create'],
            'unloading' => ['distributionnew.unloading.view','distributionnew.unloading.create'],
            'vehicles' => ['distributionnew.vehicle.view','distributionnew.vehicle.create','distributionnew.vehicle.update'],
            'reports' => ['distributionnew.reports.view'],
            'audit' => ['distributionnew.audit.view'],
        ];
    }

    public function logAudit(?int $businessId, ?int $userId, string $permission, bool $expected, bool $actual): DisnewPermissionAuditLog
    {
        return DisnewPermissionAuditLog::create([
            'business_id' => $businessId,
            'user_id' => $userId,
            'permission_key' => $permission,
            'expected_status' => $expected,
            'actual_status' => $actual,
            'status' => $expected === $actual ? 'pass' : 'mismatch',
        ]);
    }
}
