<?php

namespace Modules\BankingUI\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\BankingUI\Services\BankingRouteHealthService;

class BankingRouteHealthController extends Controller
{
    public function index(BankingRouteHealthService $health)
    {
        return view('bankingui::testing.route_health', ['routes' => $health->routes()]);
    }
}
