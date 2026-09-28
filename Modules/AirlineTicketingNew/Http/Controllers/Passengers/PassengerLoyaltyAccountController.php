<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Passengers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\PassengerLoyaltyAccount;
use Modules\AirlineTicketingNew\Entities\Passenger;

class PassengerLoyaltyAccountController extends Controller
{
    public function index(Passenger $passenger)
    {
        abort_unless((int) $passenger->business_id === (int) session('business.id'), 404);

        $records = PassengerLoyaltyAccount::query()
            ->where('business_id', (int) session('business.id'))
            ->where('passenger_id', $passenger->id)
            ->latest('id')
            ->get();

        return view('airlineticketingnew::passengers.children.loyalty-accounts', compact('passenger', 'records'));
    }

    public function store(Request $request, Passenger $passenger)
    {
        abort_unless((int) $passenger->business_id === (int) session('business.id'), 404);

        $data = $request->validate([
            'airline_id' => ['nullable','integer'],
            'program_name' => ['required','string','max:120'],
            'membership_number' => ['required','string','max:100'],
            'tier_name' => ['nullable','string','max:80'],
            'points_balance' => ['nullable','numeric','min:0'],
            'expiry_date' => ['nullable','date'],
            'is_active' => ['nullable','boolean'],
            'is_primary' => ['nullable','boolean']
        ]);

        $data['business_id'] = (int) session('business.id');
        $data['passenger_id'] = $passenger->id;
        $data['is_active'] = $request->boolean('is_active');
        if ($request->has('is_primary')) {
            $data['is_primary'] = $request->boolean('is_primary');
        }

        PassengerLoyaltyAccount::query()->create($data);

        return back()->with('status', [
            'success' => 1,
            'msg' => __('airlineticketingnew::profiles.saved_successfully'),
        ]);
    }
}
