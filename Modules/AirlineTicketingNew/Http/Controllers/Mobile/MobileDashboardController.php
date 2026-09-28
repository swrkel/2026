<?php
namespace Modules\AirlineTicketingNew\Http\Controllers\Mobile;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Services\Mobile\MobileDashboardService;

class MobileDashboardController extends Controller
{
    public function index(Request $request, MobileDashboardService $service)
    {
        return response()->json([
            'data' => $service->summary(
                (int) session('business.id'),
                auth()->id()
            ),
        ]);
    }
}
