<?php
namespace Modules\AirlineTicketingNew\Http\Controllers\Mobile;

use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Services\Mobile\MobileActionService;

class MobileActionController extends Controller
{
    public function index(MobileActionService $service)
    {
        return response()->json([
            'data'=>$service->pendingActions((int)session('business.id'),(int)auth()->id())
        ]);
    }
}
