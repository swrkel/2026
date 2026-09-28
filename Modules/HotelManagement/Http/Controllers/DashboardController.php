<?php
namespace Modules\HotelManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\HotelManagement\Http\Controllers\Concerns\HotelTenantContext;

class DashboardController extends Controller
{
    use HotelTenantContext;

    public function index()
    {
        $counts = [];
        foreach ([
            'hotels' => 'hm_hotels', 'rooms' => 'hm_rooms', 'available_rooms' => 'hm_rooms', 'reservations' => 'hm_reservations',
            'arrivals_today' => 'hm_reservations', 'departures_today' => 'hm_reservations', 'open_folios' => 'hm_folios', 'dirty_rooms' => 'hm_rooms'
        ] as $key => $table) {
            $counts[$key] = 0;
            if ($this->tableExists($table)) {
                $q = DB::table($table);
                if (Schema::hasColumn($table, 'business_id') && $this->businessId()) $q->where('business_id', $this->businessId());
                if ($key === 'available_rooms') $q->where('status', 'available');
                if ($key === 'dirty_rooms') $q->where('housekeeping_status', 'dirty');
                if ($key === 'arrivals_today') $q->whereDate('arrival_date', today());
                if ($key === 'departures_today') $q->whereDate('departure_date', today());
                if ($key === 'open_folios') $q->where('status', 'open');
                $counts[$key] = $q->count();
            }
        }
        $recentReservations = $this->safeRows('hm_reservations', 8);
        $recentRooms = $this->safeRows('hm_rooms', 8);
        return view('hotelmanagement::dashboard.index', compact('counts','recentReservations','recentRooms'));
    }
}
