<?php
namespace Modules\DistributionNew\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
class SalesRepPortalController extends Controller
{
    public function dashboard(){ return view('distributionnew::sales_rep_portal.dashboard'); }
    public function routeToday(){ return view('distributionnew::sales_rep_portal.route_today'); }
    public function orders(){ return view('distributionnew::sales_rep_portal.orders'); }
    public function collections(){ return view('distributionnew::sales_rep_portal.collections'); }
}
