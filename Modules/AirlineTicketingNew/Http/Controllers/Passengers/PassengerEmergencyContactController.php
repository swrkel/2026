<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Passengers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\PassengerEmergencyContact;
use Modules\AirlineTicketingNew\Entities\Passenger;

class PassengerEmergencyContactController extends Controller
{
    public function index(Passenger $passenger)
    {
        abort_unless((int) $passenger->business_id === (int) session('business.id'), 404);

        $records = PassengerEmergencyContact::query()
            ->where('business_id', (int) session('business.id'))
            ->where('passenger_id', $passenger->id)
            ->latest('id')
            ->get();

        return view('airlineticketingnew::passengers.children.emergency-contacts', compact('passenger', 'records'));
    }

    public function store(Request $request, Passenger $passenger)
    {
        abort_unless((int) $passenger->business_id === (int) session('business.id'), 404);

        $data = $request->validate([
            'name' => ['required','string','max:150'],
            'relationship' => ['nullable','string','max:80'],
            'phone' => ['required','string','max:40'],
            'alternate_phone' => ['nullable','string','max:40'],
            'email' => ['nullable','email','max:150'],
            'country_code' => ['nullable','string','size:2'],
            'is_active' => ['nullable','boolean'],
            'is_primary' => ['nullable','boolean']
        ]);

        $data['business_id'] = (int) session('business.id');
        $data['passenger_id'] = $passenger->id;
        $data['is_active'] = $request->boolean('is_active');
        if ($request->has('is_primary')) {
            $data['is_primary'] = $request->boolean('is_primary');
        }

        PassengerEmergencyContact::query()->create($data);

        return back()->with('status', [
            'success' => 1,
            'msg' => __('airlineticketingnew::profiles.saved_successfully'),
        ]);
    }
}
