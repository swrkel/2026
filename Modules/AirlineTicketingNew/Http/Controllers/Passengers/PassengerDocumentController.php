<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Passengers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\PassengerDocument;
use Modules\AirlineTicketingNew\Entities\Passenger;

class PassengerDocumentController extends Controller
{
    public function index(Passenger $passenger)
    {
        abort_unless((int) $passenger->business_id === (int) session('business.id'), 404);

        $records = PassengerDocument::query()
            ->where('business_id', (int) session('business.id'))
            ->where('passenger_id', $passenger->id)
            ->latest('id')
            ->get();

        return view('airlineticketingnew::passengers.children.documents', compact('passenger', 'records'));
    }

    public function store(Request $request, Passenger $passenger)
    {
        abort_unless((int) $passenger->business_id === (int) session('business.id'), 404);

        $data = $request->validate([
            'document_type' => ['required','string','max:40'],
            'document_number' => ['required','string','max:100'],
            'issuing_country_code' => ['nullable','string','size:2'],
            'issued_date' => ['nullable','date'],
            'expiry_date' => ['nullable','date','after_or_equal:issued_date'],
            'place_of_issue' => ['nullable','string','max:150'],
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

        PassengerDocument::query()->create($data);

        return back()->with('status', [
            'success' => 1,
            'msg' => __('airlineticketingnew::profiles.saved_successfully'),
        ]);
    }
}
