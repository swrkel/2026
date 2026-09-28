<?php
namespace Modules\AirlineTicketingNew\Http\Controllers\Dashboard;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Services\Dashboard\ManagementDashboardService;
class ManagementDashboardController extends Controller {
    public function index(ManagementDashboardService $s){
        return view('airlineticketingnew::dashboard.management',['metrics'=>$s->metrics((int)session('business.id'))]);
    }
}
