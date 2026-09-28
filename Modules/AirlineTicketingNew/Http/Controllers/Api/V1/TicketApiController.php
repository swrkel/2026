<?php
namespace Modules\AirlineTicketingNew\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\Ticket;

class TicketApiController extends Controller
{
    public function index(Request $request)
    {
        $businessId = (int) $request->attributes->get('atn_api_business_id');

        return response()->json(
            Ticket::query()
                ->where('business_id', $businessId)
                ->latest('id')
                ->paginate(min(100, max(1, $request->integer('per_page', 25))))
        );
    }
}
