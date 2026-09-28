<?php

namespace Modules\HotelManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HotelManagement\Services\GuestCommunicationService;

class GuestCommunicationController extends Controller
{
    protected GuestCommunicationService $service;

    public function __construct(GuestCommunicationService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $communication = $this->service->dashboard();
        return view('hotelmanagement::communication.index', compact('communication'));
    }

    public function template(Request $request)
    {
        $data = $request->validate([
            'code' => 'nullable|string|max:60',
            'name' => 'required|string|max:120',
            'channel' => 'required|string|max:30',
            'event_key' => 'nullable|string|max:60',
            'message_body' => 'required|string|max:1000',
            'is_active' => 'nullable',
        ]);
        $this->service->saveTemplate($data, optional($request->user())->id);
        return redirect()->route('hotel-management.communication.index')->with('status', 'Hotel guest message template saved successfully.');
    }

    public function queue(Request $request)
    {
        $data = $request->validate([
            'guest_id' => 'nullable|integer',
            'reservation_id' => 'nullable|integer',
            'folio_id' => 'nullable|integer',
            'channel' => 'required|string|max:30',
            'recipient' => 'required|string|max:191',
            'subject' => 'nullable|string|max:191',
            'message_body' => 'required|string|max:1000',
        ]);
        $this->service->queueManual($data, optional($request->user())->id);
        return redirect()->route('hotel-management.communication.index')->with('status', 'Hotel guest message queued for Communication/SMS bridge.');
    }
}
