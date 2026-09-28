<?php
namespace Modules\HotelManagement\Services\Dashboard;

use Illuminate\Support\Facades\DB;

class HotelDashboardService
{
    public function owner(): array
    {
        return [
            'rooms' => DB::table('hm_rooms')->count(),
            'reservations_today' => DB::table('hm_reservations')->whereDate('created_at', today())->count(),
            'checkins_today' => DB::table('hm_checkins')->whereDate('created_at', today())->count(),
            'checkouts_today' => DB::table('hm_checkouts')->whereDate('created_at', today())->count(),
            'revenue_today' => (float) DB::table('hm_guest_payments')->whereDate('created_at', today())->sum('amount'),
            'open_housekeeping' => DB::table('hm_housekeeping_tasks')->whereNull('deleted_at')->count(),
        ];
    }

    public function manager(): array { return $this->owner(); }
    public function frontOffice(): array { return $this->owner(); }
    public function housekeeping(): array { return $this->owner(); }
    public function finance(): array { return $this->owner(); }
}
