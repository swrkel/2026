<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Passengers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\PassengerVisa;
use Modules\AirlineTicketingNew\Entities\Passenger;

class PassengerVisaController extends Controller
{
    public function index(Passenger $passenger)
    {
        abort_unless((int) $passenger->business_id === (int) session('business.id'), 404);

        $records = PassengerVisa::query()
            ->where('business_id', (int) session('business.id'))
            ->where('passenger_id', $passenger->id)
            ->latest('id')
            ->get();

        return view('airlineticketingnew::passengers.children.visas', compact('passenger', 'records'));
    }

    public function store(Request $request, Passenger $passenger)
    {
        abort_unless((int) $passenger->business_id === (int) session('business.id'), 404);

        $data = $request->validate([
            'country_code' => ['required','string','size:2'],
            'visa_type' => ['required','string','max:80'],
            'visa_number' => ['nullable','string','max:100'],
            'issued_date' => ['nullable','date'],
            'expiry_date' => ['nullable','date','after_or_equal:issued_date'],
            'entries_allowed' => ['nullable','string','max:40'],
            'status' => ['nullable','string','max:40'],
            'notes' => ['nullable','string'],
            'is_active' => ['nullable','boolean'],
            'is_primary' => ['nullable','boolean']
        ]);

        $data['business_id'] = (int) session('business.id');
        $data['passenger_id'] = $passenger->id;
        $data['is_active'] = $request->boolean('is_active');
        if ($request->has('is_primary')) {
            $data['is_primary'] = $request->boolean('is_primary');
        }

        PassengerVisa::query()->create($data);

        return back()->with('status', [
            'success' => 1,
            'msg' => __('airlineticketingnew::profiles.saved_successfully'),
        ]);
    }
}
