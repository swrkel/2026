<?php

namespace Modules\SimpleAudit\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ContextService
{
    protected $tenants;

    public function __construct(TenantConnectionManager $tenants)
    {
        $this->tenants = $tenants;
    }

    public function tenants()
    {
        return $this->tenants->listTenants();
    }

    public function businesses($tenantId = null)
    {
        $connection = $this->tenants->connectionForTenant($tenantId);
        return DB::connection($connection)->table('business')
            ->where(function ($q) {
                $q->whereNull('is_active')->orWhere('is_active', 1);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'tenant_id', 'currency_precision', 'quantity_precision', 'fy_start_month'])
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'name' => $row->name,
                    'tenant_id' => $row->tenant_id,
                    'currency_precision' => $row->currency_precision,
                    'quantity_precision' => $row->quantity_precision,
                    'fy_start_month' => $row->fy_start_month,
                ];
            })->all();
    }

    public function locations($tenantId, $businessId)
    {
        $connection = $this->tenants->connectionForTenant($tenantId);
        return DB::connection($connection)->table('business_locations')
            ->where('business_id', $businessId)
            ->whereNull('deleted_at')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'location_id', 'city'])
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'name' => $row->name,
                    'code' => $row->location_id,
                    'city' => $row->city,
                ];
            })->all();
    }

    public function stores($tenantId, $businessId, $locationId = null)
    {
        $connection = $this->tenants->connectionForTenant($tenantId);
        if (!Schema::connection($connection)->hasTable('stores')) {
            return [];
        }

        $query = DB::connection($connection)->table('stores')
            ->where('business_id', $businessId)
            ->where('status', 1);
        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        return $query->orderBy('name')->get(['id', 'name', 'location_id', 'is_main'])
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'name' => $row->name,
                    'location_id' => (int) $row->location_id,
                    'is_main' => (int) $row->is_main,
                ];
            })->all();
    }

    public function businessMeta($connection, $businessId)
    {
        $row = DB::connection($connection)->table('business')->where('id', $businessId)->first();
        if (!$row) {
            throw new RuntimeException(__('simpleaudit::simpleaudit.business_not_found'));
        }

        return [
            'id' => (int) $row->id,
            'name' => $row->name,
            'tenant_id' => isset($row->tenant_id) ? $row->tenant_id : null,
            'currency_precision' => max(0, min(6, (int) ($row->currency_precision !== null ? $row->currency_precision : 2))),
            'quantity_precision' => max(0, min(6, (int) ($row->quantity_precision !== null ? $row->quantity_precision : 2))),
            'fy_start_month' => max(1, min(12, (int) ($row->fy_start_month ?: config('simpleaudit.default_fy_start_month', 4)))),
            'time_zone' => !empty($row->time_zone) ? (string) $row->time_zone : 'Asia/Colombo',
        ];
    }
}
