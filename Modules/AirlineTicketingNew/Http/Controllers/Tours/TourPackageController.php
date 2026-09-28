<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Tours;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\TourPackage;

class TourPackageController extends Controller
{
    public function index()
    {
        $records = TourPackage::query()->forBusiness()->latest('id')->paginate(25);
        return view('airlineticketingnew::tours.packages.index', compact('records'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'package_code' => ['required','string','max:40'],
            'name' => ['required','string','max:180'],
            'package_type' => ['required','in:domestic,international'],
            'destination_country_code' => ['nullable','string','size:2'],
            'destination_city' => ['required','string','max:100'],
            'duration_days' => ['required','integer','min:1'],
            'duration_nights' => ['required','integer','min:0'],
            'currency_code' => ['required','string','size:3'],
            'cost_amount' => ['required','numeric','min:0'],
            'sale_amount' => ['required','numeric','min:0'],
            'max_passengers' => ['nullable','integer','min:1'],
            'status' => ['required','in:draft,active,inactive'],
            'description' => ['nullable','string'],
        ]);

        TourPackage::query()->create(array_merge($data, [
            'business_id' => (int)session('business.id'),
            'business_location_id' => $request->integer('business_location_id') ?: null,
            'store_id' => $request->integer('store_id') ?: null,
        ]));

        return back()->with('status', ['success' => 1, 'msg' => 'Tour package saved successfully.']);
    }
}
