<?php

namespace Modules\HotelManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ProductionHardeningService
{
    protected array $coreTables = [
        'hm_hotels','hm_rooms','hm_room_types','hm_rate_plans','hm_reservations','hm_reservation_rooms',
        'hm_check_ins','hm_check_outs','hm_folios','hm_folio_lines','hm_guest_payments','hm_store_items',
        'hm_store_movements','hm_pos_orders','hm_pos_order_lines','hm_housekeeping_schedules','hm_room_service_orders',
        'hm_banquet_events','hm_conference_bookings','hm_night_audits'
    ];

    protected array $routeNames = [
        'hotel-management.dashboard','hotel-management.hotels.index','hotel-management.rooms.index',
        'hotel-management.rate-plans.index','hotel-management.reservations.index','hotel-management.front-office.index',
        'hotel-management.housekeeping.index','hotel-management.maintenance.index','hotel-management.billing.index',
        'hotel-management.pos.index','hotel-management.room-service.index','hotel-management.inventory.index',
        'hotel-management.crm.index','hotel-management.banquets.index','hotel-management.conference.index',
        'hotel-management.night-audit.index','hotel-management.reports.index','hotel-management.production-hardening.index'
    ];

    public function buildAudit(): array
    {
        $tableScope = $this->tableScopeChecks();
        $routeChecks = $this->routeChecks();
        $indexChecks = $this->indexRecommendations();
        $integrationChecks = $this->integrationChecks();
        $counts = $this->safeCounts();

        return [
            'summary' => [
                'tables_scoped' => collect($tableScope)->where('status', true)->count(),
                'tables_total' => count($tableScope),
                'routes_ok' => collect($routeChecks)->where('status', true)->count(),
                'routes_total' => count($routeChecks),
                'indexes_recommended' => collect($indexChecks)->where('status', false)->count(),
                'integrations_ok' => collect($integrationChecks)->where('status', true)->count(),
                'integrations_total' => count($integrationChecks),
            ],
            'tableScope' => $tableScope,
            'routes' => $routeChecks,
            'indexes' => $indexChecks,
            'integrations' => $integrationChecks,
            'counts' => $counts,
        ];
    }

    protected function tableScopeChecks(): array
    {
        $checks = [];
        foreach ($this->coreTables as $table) {
            $hasTable = Schema::hasTable($table);
            $checks[] = [
                'area' => 'Tenant/Business Scope',
                'name' => $table,
                'status' => $hasTable && Schema::hasColumn($table, 'business_id') && Schema::hasColumn($table, 'business_location_id'),
                'note' => $hasTable ? 'business_id and business_location_id required for multi-business isolation' : 'Table missing',
            ];
        }
        return $checks;
    }

    protected function routeChecks(): array
    {
        return collect($this->routeNames)->map(fn ($route) => [
            'area' => 'Route Binding',
            'name' => $route,
            'status' => Route::has($route),
            'note' => Route::has($route) ? 'OK' : 'Missing route binding',
        ])->values()->all();
    }

    protected function indexRecommendations(): array
    {
        $recommendations = [
            ['hm_rooms', ['business_id','business_location_id','status']],
            ['hm_reservations', ['business_id','business_location_id','status','arrival_date']],
            ['hm_folios', ['business_id','business_location_id','status']],
            ['hm_folio_lines', ['business_id','folio_id','posting_date']],
            ['hm_guest_payments', ['business_id','folio_id','payment_date']],
            ['hm_store_movements', ['business_id','business_location_id','movement_date']],
            ['hm_pos_orders', ['business_id','business_location_id','order_date']],
            ['hm_housekeeping_schedules', ['business_id','business_location_id','work_date','status']],
            ['hm_night_audits', ['business_id','business_location_id','audit_date']],
        ];

        return collect($recommendations)->map(function ($item) {
            [$table, $columns] = $item;
            return [
                'area' => 'Performance Index',
                'name' => $table.' ('.implode(', ', $columns).')',
                'status' => Schema::hasTable($table),
                'note' => Schema::hasTable($table) ? 'Index included in HOTELMGT_018_SQL.sql using safe conditional statements' : 'Table missing; run master SQL first',
            ];
        })->values()->all();
    }

    protected function integrationChecks(): array
    {
        $checks = [
            ['Customers module contact table', 'contacts'],
            ['Business locations', 'business_locations'],
            ['Accounting transactions', 'transactions'],
            ['Communication Hub / SMS settings', 'sms_settings'],
            ['Hotel menu registry', 'hm_menu_registry'],
            ['Hotel module permissions', 'hm_module_permissions'],
        ];

        return collect($checks)->map(fn ($item) => [
            'area' => 'ERP Integration',
            'name' => $item[0],
            'status' => Schema::hasTable($item[1]),
            'note' => Schema::hasTable($item[1]) ? 'Available' : 'Not found in this tenant database',
        ])->values()->all();
    }

    protected function safeCounts(): array
    {
        $counts = [];
        foreach (['hm_hotels','hm_rooms','hm_reservations','hm_folios','hm_pos_orders','hm_store_items','hm_housekeeping_schedules'] as $table) {
            $counts[$table] = Schema::hasTable($table) ? $this->safeCount($table) : 0;
        }
        return $counts;
    }

    protected function safeCount(string $table): int
    {
        try { return (int) DB::table($table)->count(); } catch (Throwable $e) { return 0; }
    }
}
