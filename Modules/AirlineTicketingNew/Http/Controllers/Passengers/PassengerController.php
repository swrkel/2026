<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Passengers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\Passenger;
use Modules\AirlineTicketingNew\Http\Requests\Passengers\SavePassengerRequest;
use Modules\AirlineTicketingNew\Services\Passengers\PassengerProfileService;
use Modules\AirlineTicketingNew\Services\Passengers\ProfileNumberService;

class PassengerController extends Controller
{
    public function index(Request $request)
    {
        $records = Passenger::query()
            ->forBusiness()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($inner) use ($search): void {
                    $inner->where('passenger_no', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('airlineticketingnew::passengers.index', compact('records'));
    }

    public function create()
    {
        return view('airlineticketingnew::passengers.form', ['record' => new Passenger()]);
    }

    public function store(
        SavePassengerRequest $request,
        ProfileNumberService $numberService,
        PassengerProfileService $profileService
    ) {
        $businessId = (int) session('business.id');
        $data = array_merge($request->validated(), [
            'business_id' => $businessId,
            'business_location_id' => $request->integer('business_location_id') ?: null,
            'store_id' => $request->integer('store_id') ?: null,
            'passenger_no' => $numberService->next($businessId, 'passenger', 'PAX'),
        ]);

        $profileService->create($data);

        return redirect()->route('airline-ticketing-new.passengers.index')
            ->with('status', ['success' => 1, 'msg' => __('airlineticketingnew::profiles.saved_successfully')]);
    }

    public function edit(Passenger $passenger)
    {
        abort_unless((int) $passenger->business_id === (int) session('business.id'), 404);
        return view('airlineticketingnew::passengers.form', ['record' => $passenger]);
    }

    public function update(
        SavePassengerRequest $request,
        Passenger $passenger,
        PassengerProfileService $profileService
    ) {
        abort_unless((int) $passenger->business_id === (int) session('business.id'), 404);
        $profileService->update($passenger, $request->validated());

        return redirect()->route('airline-ticketing-new.passengers.index')
            ->with('status', ['success' => 1, 'msg' => __('airlineticketingnew::profiles.saved_successfully')]);
    }
}
