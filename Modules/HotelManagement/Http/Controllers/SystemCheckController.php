<?php

namespace Modules\HotelManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class SystemCheckController extends Controller
{
    public function index(Request $request)
    {
        $requiredTables = [
            'hm_hotels','hm_rooms','hm_room_types','hm_rate_plans','hm_reservations','hm_reservation_rooms',
            'hm_check_ins','hm_check_outs','hm_folios','hm_folio_lines','hm_guest_payments','hm_housekeeping_tasks',
            'hm_maintenance_work_orders','hm_store_items','hm_store_movements','hm_pos_menu_items','hm_pos_orders',
            'hm_housekeeping_schedules','hm_lost_found_items','hm_linen_movements','hm_room_service_orders',
            'hm_banquet_halls','hm_banquet_events','hm_conference_rooms','hm_conference_bookings',
            'hm_night_audits','hm_night_audit_lines','hm_module_permissions','hm_menu_registry'
        ];

        $requiredRoutes = [
            'hotel-management.dashboard','hotel-management.hotels.index','hotel-management.rooms.index',
            'hotel-management.rate-plans.index','hotel-management.reservations.index','hotel-management.front-office.index',
            'hotel-management.housekeeping.index','hotel-management.maintenance.index','hotel-management.billing.index',
            'hotel-management.pos.index','hotel-management.inventory.index','hotel-management.crm.index',
            'hotel-management.banquets.index','hotel-management.conference.index','hotel-management.room-service.index',
            'hotel-management.night-audit.index','hotel-management.reports.index','hotel-management.system-check.index'
        ];

        $tableChecks = collect($requiredTables)->map(function ($table) {
            return [
                'name' => $table,
                'status' => Schema::hasTable($table),
                'type' => 'table',
            ];
        })->values();

        $routeChecks = collect($requiredRoutes)->map(function ($route) {
            return [
                'name' => $route,
                'status' => Route::has($route),
                'type' => 'route',
            ];
        })->values();

        $scopeChecks = [];
        foreach (['hm_hotels','hm_rooms','hm_reservations','hm_folios','hm_pos_orders','hm_store_items'] as $table) {
            $scopeChecks[] = [
                'name' => $table.' business_id',
                'status' => Schema::hasTable($table) && Schema::hasColumn($table, 'business_id'),
                'type' => 'tenant/business scope',
            ];
            $scopeChecks[] = [
                'name' => $table.' business_location_id',
                'status' => Schema::hasTable($table) && Schema::hasColumn($table, 'business_location_id'),
                'type' => 'tenant/business scope',
            ];
        }

        $menuRecords = Schema::hasTable('hm_menu_registry')
            ? DB::table('hm_menu_registry')->where('module', 'HotelManagement')->where('is_active', 1)->count()
            : 0;
        $permissionRecords = Schema::hasTable('hm_module_permissions')
            ? DB::table('hm_module_permissions')->where('module', 'HotelManagement')->where('is_active', 1)->count()
            : 0;

        $summary = [
            'tables_total' => $tableChecks->count(),
            'tables_ok' => $tableChecks->where('status', true)->count(),
            'routes_total' => $routeChecks->count(),
            'routes_ok' => $routeChecks->where('status', true)->count(),
            'scope_total' => count($scopeChecks),
            'scope_ok' => collect($scopeChecks)->where('status', true)->count(),
            'menu_records' => $menuRecords,
            'permission_records' => $permissionRecords,
        ];

        return view('hotelmanagement::system_check.index', compact('tableChecks', 'routeChecks', 'scopeChecks', 'summary'));
    }
}
