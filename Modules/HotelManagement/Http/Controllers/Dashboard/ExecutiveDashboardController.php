<?php
namespace Modules\HotelManagement\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Modules\HotelManagement\Services\Dashboard\HotelDashboardService;

class ExecutiveDashboardController extends Controller
{
    public function __construct(protected HotelDashboardService $dashboard) {}
    public function owner() { return view('hotelmanagement::dashboard.executive', ['title' => 'Owner Dashboard', 'kpis' => $this->dashboard->owner()]); }
    public function manager() { return view('hotelmanagement::dashboard.executive', ['title' => 'Hotel Manager Dashboard', 'kpis' => $this->dashboard->manager()]); }
    public function frontOffice() { return view('hotelmanagement::dashboard.executive', ['title' => 'Front Office Dashboard', 'kpis' => $this->dashboard->frontOffice()]); }
    public function housekeeping() { return view('hotelmanagement::dashboard.executive', ['title' => 'Housekeeping Dashboard', 'kpis' => $this->dashboard->housekeeping()]); }
    public function finance() { return view('hotelmanagement::dashboard.executive', ['title' => 'Finance Dashboard', 'kpis' => $this->dashboard->finance()]); }
}
