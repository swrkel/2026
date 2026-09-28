<?php

namespace Modules\HotelManagement\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Throwable;

class FinalReadinessService
{
    protected array $routeGroups = [
        'Core' => ['hotel-management.dashboard','hotel-management.hotels.index','hotel-management.rooms.index','hotel-management.rate-plans.index'],
        'Front Office' => ['hotel-management.reservations.index','hotel-management.front-office.index','hotel-management.billing.index','hotel-management.crm.index'],
        'Operations' => ['hotel-management.housekeeping.index','hotel-management.maintenance.index','hotel-management.inventory.index','hotel-management.room-service.index'],
        'Revenue' => ['hotel-management.pos.index','hotel-management.banquets.index','hotel-management.conference.index','hotel-management.night-audit.index','hotel-management.reports.index'],
        'Control' => ['hotel-management.system-check.index','hotel-management.testing-readiness.index','hotel-management.production-hardening.index','hotel-management.data-integrity.index','hotel-management.final-readiness.index'],
    ];

    protected array $requiredTables = [
        'hm_hotels','hm_rooms','hm_room_types','hm_rate_plans','hm_guests','hm_reservations','hm_reservation_rooms',
        'hm_check_ins','hm_check_outs','hm_folios','hm_folio_lines','hm_guest_payments','hm_maintenance_work_orders',
        'hm_housekeeping_schedules','hm_lost_found_items','hm_linen_movements','hm_store_items','hm_store_movements',
        'hm_pos_menu_items','hm_pos_orders','hm_pos_order_lines','hm_room_service_orders','hm_room_service_order_lines',
        'hm_banquet_halls','hm_banquet_events','hm_conference_rooms','hm_conference_bookings','hm_night_audits',
        'hm_analytics_snapshots','hm_module_permissions','hm_menu_registry','hm_testing_notes','hm_production_checks','hm_data_integrity_snapshots'
    ];

    protected array $permissionKeys = [
        'hotel.view','hotel.manage','hotel.setup','hotel.rooms','hotel.rates','hotel.reservations','hotel.front_office',
        'hotel.billing','hotel.housekeeping','hotel.maintenance','hotel.inventory','hotel.pos','hotel.room_service',
        'hotel.crm','hotel.banquets','hotel.conference','hotel.night_audit','hotel.reports','hotel.audit','hotel.data_integrity'
    ];

    public function buildReport(): array
    {
        $routes = $this->routeChecks();
        $tables = $this->tableChecks();
        $permissions = $this->permissionChecks();
        $scopes = $this->scopeChecks();

        $total = count($routes) + count($tables) + count($permissions) + count($scopes);
        $ok = collect(array_merge($routes, $tables, $permissions, $scopes))->where('status', true)->count();

        return [
            'summary' => [
                'ok' => $ok,
                'total' => $total,
                'percent' => $total ? round(($ok / $total) * 100, 1) : 0,
                'routes' => collect($routes)->where('status', true)->count().'/'.count($routes),
                'tables' => collect($tables)->where('status', true)->count().'/'.count($tables),
                'permissions' => collect($permissions)->where('status', true)->count().'/'.count($permissions),
                'scopes' => collect($scopes)->where('status', true)->count().'/'.count($scopes),
            ],
            'routes' => $routes,
            'tables' => $tables,
            'permissions' => $permissions,
            'scopes' => $scopes,
            'notes' => $this->deploymentNotes(),
        ];
    }

    protected function routeChecks(): array
    {
        $rows = [];
        foreach ($this->routeGroups as $group => $routes) {
            foreach ($routes as $route) {
                $rows[] = ['area' => 'Route', 'group' => $group, 'name' => $route, 'status' => Route::has($route), 'note' => Route::has($route) ? 'Bound' : 'Missing route binding'];
            }
        }
        return $rows;
    }

    protected function tableChecks(): array
    {
        return collect($this->requiredTables)->map(fn ($table) => [
            'area' => 'Table', 'group' => 'Database', 'name' => $table,
            'status' => Schema::hasTable($table),
            'note' => Schema::hasTable($table) ? 'Available' : 'Run HOTELMGT_MASTER_SQL.sql on tenant database',
        ])->values()->all();
    }

    protected function permissionChecks(): array
    {
        return collect($this->permissionKeys)->map(function ($key) {
            $exists = false;
            try {
                if (Schema::hasTable('permissions')) {
                    $exists = DB::table('permissions')->where('name', $key)->exists();
                } elseif (Schema::hasTable('hm_module_permissions')) {
                    $exists = DB::table('hm_module_permissions')->where('permission_key', $key)->exists();
                }
            } catch (Throwable $e) { $exists = false; }
            return ['area' => 'Permission', 'group' => 'Access Control', 'name' => $key, 'status' => $exists, 'note' => $exists ? 'Registered' : 'Permission seed required'];
        })->values()->all();
    }

    protected function scopeChecks(): array
    {
        $scopeTables = array_filter($this->requiredTables, fn ($table) => Schema::hasTable($table));
        return collect($scopeTables)->map(fn ($table) => [
            'area' => 'Scope', 'group' => 'Tenant/Business', 'name' => $table,
            'status' => Schema::hasColumn($table, 'business_id') && Schema::hasColumn($table, 'business_location_id'),
            'note' => 'Requires business_id and business_location_id for multi-business isolation',
        ])->values()->all();
    }

    protected function deploymentNotes(): array
    {
        return [
            'Replace the full HotelManagement module folder from this ZIP.',
            'Run only Docs/HOTELMGT_020_SQL.sql if tenant databases already have SQL up to parcel 019.',
            'Run Docs/HOTELMGT_MASTER_SQL.sql only for a fresh tenant or tenant that missed earlier parcels.',
            'Clear Laravel config, route and view cache after upload.',
            'Open Hotel Management > Data Integrity, save a snapshot, then open Final Readiness and confirm all route/table/scope checks before client testing.',
        ];
    }
}
