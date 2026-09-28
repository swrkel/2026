<?php

namespace Modules\RestaurantNew\Http\Controllers\Reservation;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Entities\RestaurantNewFloorPlan;
use Modules\RestaurantNew\Entities\RestaurantNewFloorTable;
use Modules\RestaurantNew\Entities\RestaurantNewReservation;
use Modules\RestaurantNew\Entities\RestaurantNewWaitlist;
use Modules\RestaurantNew\Services\Reservation\RestaurantReservationService;

class RestaurantReservationController extends Controller
{
    public function __construct(private RestaurantReservationService $service) {}

    public function dashboard(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $locationId = $request->get('location_id');
        $summary = $this->service->dashboard($businessId, $locationId ? (int)$locationId : null);
        return view('restaurantnew::reservation.dashboard', compact('summary'));
    }

    public function floorPlans(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $plans = RestaurantNewFloorPlan::where('business_id', $businessId)->latest()->paginate(25);
        return view('restaurantnew::reservation.floor_plans.index', compact('plans'));
    }

    public function reservations(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $reservations = RestaurantNewReservation::where('business_id', $businessId)->latest()->paginate(25);
        return view('restaurantnew::reservation.reservations.index', compact('reservations'));
    }

    public function waitlist(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $waitlist = RestaurantNewWaitlist::where('business_id', $businessId)->whereIn('status', ['waiting','called'])->latest()->paginate(25);
        return view('restaurantnew::reservation.waitlist.index', compact('waitlist'));
    }

    public function storeReservation(Request $request)
    {
        $data = $request->validate([
            'reservation_no' => 'required|string|max:80',
            'customer_name' => 'required|string|max:191',
            'mobile' => 'nullable|string|max:40',
            'reservation_date' => 'required|date',
            'reservation_time' => 'required',
            'guest_count' => 'required|integer|min:1',
            'floor_table_id' => 'nullable|integer',
        ]);
        $data['business_id'] = (int) $request->session()->get('user.business_id');
        $data['location_id'] = $request->get('location_id');
        $data['created_by'] = auth()->id();
        $reservation = $this->service->createReservation($data);
        return response()->json(['success' => true, 'reservation' => $reservation]);
    }

    public function checkIn(Request $request, RestaurantNewReservation $reservation)
    {
        $reservation = $this->service->checkIn($reservation, $request->integer('floor_table_id'), auth()->id());
        return response()->json(['success' => true, 'reservation' => $reservation]);
    }
}
