<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Hotels;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\Hotel;

class HotelController extends Controller
{
    public function index()
    {
        $records = Hotel::query()->forBusiness()->latest('id')->paginate(25);
        return view('airlineticketingnew::hotels.index', compact('records'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'hotel_code' => ['required','string','max:40'],
            'name' => ['required','string','max:180'],
            'country_code' => ['required','string','size:2'],
            'city' => ['required','string','max:100'],
            'address' => ['nullable','string'],
            'phone' => ['nullable','string','max:40'],
            'email' => ['nullable','email','max:150'],
            'star_rating' => ['nullable','integer','min:1','max:5'],
            'supplier_id' => ['nullable','integer'],
            'is_active' => ['nullable','boolean'],
        ]);

        Hotel::query()->create(array_merge($data, [
            'business_id' => (int)session('business.id'),
            'business_location_id' => $request->integer('business_location_id') ?: null,
            'store_id' => $request->integer('store_id') ?: null,
            'is_active' => $request->boolean('is_active'),
        ]));

        return back()->with('status', ['success' => 1, 'msg' => 'Hotel saved successfully.']);
    }
}
