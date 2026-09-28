<?php

namespace Modules\HotelManagement\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Modules\HotelManagement\Http\Controllers\Concerns\HotelTenantContext;

class TestingReadinessController extends Controller
{
    use HotelTenantContext;

    public function index(Request $request)
    {
        $context = $this->hotelContext();
        $businessId = $context['business_id'] ?? null;
        $locationId = $context['business_location_id'] ?? null;

        $modules = [
            'Setup' => ['hm_hotels', 'hm_rooms', 'hm_room_types', 'hm_rate_plans'],
            'Reservations' => ['hm_reservations', 'hm_reservation_rooms'],
            'Front Office' => ['hm_check_ins', 'hm_check_outs', 'hm_folios', 'hm_folio_lines'],
            'Housekeeping' => ['hm_housekeeping_schedules', 'hm_lost_found_items', 'hm_linen_movements'],
            'POS & Room Service' => ['hm_pos_orders', 'hm_pos_order_lines', 'hm_room_service_orders'],
            'Events' => ['hm_banquet_halls', 'hm_banquet_events', 'hm_conference_rooms', 'hm_conference_bookings'],
            'Night Audit' => ['hm_night_audits', 'hm_night_audit_lines'],
            'Reports' => ['hm_report_snapshots', 'hm_report_exports'],
        ];

        $rows = [];
        foreach ($modules as $area => $tables) {
            foreach ($tables as $table) {
                $exists = Schema::hasTable($table);
                $count = null;
                $scoped = null;
                if ($exists) {
                    try {
                        $query = DB::table($table);
                        if (Schema::hasColumn($table, 'business_id') && $businessId) {
                            $query->where('business_id', $businessId);
                            $scoped = 'Business';
                        }
                        if (Schema::hasColumn($table, 'business_location_id') && $locationId) {
                            $query->where('business_location_id', $locationId);
                            $scoped = trim(($scoped ? $scoped.' + ' : '').'Location');
                        }
                        $count = $query->count();
                    } catch (\Throwable $e) {
                        $count = 'Error';
                    }
                }
                $rows[] = compact('area', 'table', 'exists', 'count', 'scoped');
            }
        }

        $routeNames = [
            'hotel-management.dashboard',
            'hotel-management.hotels.index',
            'hotel-management.rooms.index',
            'hotel-management.reservations.index',
            'hotel-management.front-office.index',
            'hotel-management.housekeeping.index',
            'hotel-management.billing.index',
            'hotel-management.pos.index',
            'hotel-management.room-service.index',
            'hotel-management.inventory.index',
            'hotel-management.crm.index',
            'hotel-management.maintenance.index',
            'hotel-management.banquets.index',
            'hotel-management.conference.index',
            'hotel-management.night-audit.index',
            'hotel-management.reports.index',
            'hotel-management.system-check.index',
            'hotel-management.testing-readiness.index',
        ];

        $routes = [];
        foreach ($routeNames as $name) {
            $routes[] = [
                'name' => $name,
                'exists' => Route::has($name),
                'url' => Route::has($name) ? route($name) : null,
            ];
        }

        $summary = [
            'business_id' => $businessId,
            'business_location_id' => $locationId,
            'tables_ok' => collect($rows)->where('exists', true)->count(),
            'tables_total' => count($rows),
            'routes_ok' => collect($routes)->where('exists', true)->count(),
            'routes_total' => count($routes),
        ];

        return view('hotelmanagement::testing_readiness.index', compact('rows', 'routes', 'summary'));
    }

    public function record(Request $request)
    {
        $context = $this->hotelContext();
        if (Schema::hasTable('hm_testing_readiness_logs')) {
            DB::table('hm_testing_readiness_logs')->insert([
                'business_id' => $context['business_id'] ?? null,
                'business_location_id' => $context['business_location_id'] ?? null,
                'checked_by' => auth()->id(),
                'check_title' => $request->input('check_title', 'Manual readiness check'),
                'check_status' => $request->input('check_status', 'passed'),
                'notes' => $request->input('notes'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return back()->with('status', 'Hotel testing readiness note saved successfully.');
    }
}
