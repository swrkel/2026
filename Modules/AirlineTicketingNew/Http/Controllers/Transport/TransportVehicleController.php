<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Transport;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\TransportVehicle;

class TransportVehicleController extends Controller
{
    public function index()
    {
        $records = TransportVehicle::query()->forBusiness()->latest('id')->paginate(25);
        return view('airlineticketingnew::transport.vehicles.index', compact('records'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'vehicle_no' => ['required','string','max:40'],
            'vehicle_type' => ['required','string','max:80'],
            'make' => ['nullable','string','max:80'],
            'model' => ['nullable','string','max:80'],
            'seat_capacity' => ['required','integer','min:1'],
            'supplier_id' => ['nullable','integer'],
            'driver_name' => ['nullable','string','max:150'],
            'driver_phone' => ['nullable','string','max:40'],
            'is_active' => ['nullable','boolean'],
        ]);

        TransportVehicle::query()->create(array_merge($data, [
            'business_id' => (int)session('business.id'),
            'business_location_id' => $request->integer('business_location_id') ?: null,
            'store_id' => $request->integer('store_id') ?: null,
            'is_active' => $request->boolean('is_active'),
        ]));

        return back()->with('status', ['success' => 1, 'msg' => 'Vehicle saved successfully.']);
    }
}
