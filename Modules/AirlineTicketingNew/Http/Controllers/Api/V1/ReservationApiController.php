<?php
namespace Modules\AirlineTicketingNew\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\Reservation;

class ReservationApiController extends Controller
{
    public function index(Request $request)
    {
        $businessId = (int) $request->attributes->get('atn_api_business_id');

        return response()->json(
            Reservation::query()
                ->where('business_id', $businessId)
                ->latest('id')
                ->paginate(min(100, max(1, $request->integer('per_page', 25))))
        );
    }

    public function show(Request $request, Reservation $reservation)
    {
        abort_unless(
            (int) $reservation->business_id === (int) $request->attributes->get('atn_api_business_id'),
            404
        );

        return response()->json($reservation->load(['segments', 'passengers']));
    }
}
