<?php

namespace Modules\HotelManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HotelManagement\Services\ChannelManagerService;

class ChannelManagerController extends Controller
{
    protected ChannelManagerService $service;

    public function __construct(ChannelManagerService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $channelManager = $this->service->dashboard();
        return view('hotelmanagement::channel_manager.index', compact('channelManager'));
    }

    public function channel(Request $request)
    {
        $data = $request->validate([
            'channel_name' => 'required|string|max:160',
            'channel_code' => 'nullable|string|max:60',
            'channel_type' => 'nullable|string|max:60',
            'contact_email' => 'nullable|email|max:160',
            'commission_percent' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'nullable',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->saveChannel($data, optional($request->user())->id);
        return redirect()->route('hotel-management.channel-manager.index')->with('status', 'Hotel sales channel saved successfully.');
    }

    public function rateMap(Request $request)
    {
        $data = $request->validate([
            'channel_id' => 'required|integer',
            'room_type_id' => 'nullable|integer',
            'rate_plan_id' => 'nullable|integer',
            'external_room_code' => 'nullable|string|max:100',
            'external_rate_code' => 'nullable|string|max:100',
            'sell_rate' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'is_active' => 'nullable',
        ]);
        $this->service->saveRateMap($data, optional($request->user())->id);
        return redirect()->route('hotel-management.channel-manager.index')->with('status', 'Channel rate mapping saved successfully.');
    }

    public function availability(Request $request)
    {
        $data = $request->validate([
            'channel_id' => 'required|integer',
            'room_type_id' => 'nullable|integer',
            'available_date' => 'required|date',
            'available_rooms' => 'required|integer|min:0',
            'stop_sell' => 'nullable',
            'min_stay' => 'nullable|integer|min:0',
            'max_stay' => 'nullable|integer|min:0',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->saveAvailability($data, optional($request->user())->id);
        return redirect()->route('hotel-management.channel-manager.index')->with('status', 'Channel availability updated successfully.');
    }

    public function booking(Request $request)
    {
        $data = $request->validate([
            'channel_id' => 'required|integer',
            'external_booking_ref' => 'required|string|max:120',
            'guest_name' => 'required|string|max:160',
            'guest_mobile' => 'nullable|string|max:50',
            'guest_email' => 'nullable|email|max:160',
            'arrival_date' => 'required|date',
            'departure_date' => 'required|date|after_or_equal:arrival_date',
            'rooms' => 'nullable|integer|min:1',
            'adults' => 'nullable|integer|min:0',
            'children' => 'nullable|integer|min:0',
            'gross_amount' => 'nullable|numeric|min:0',
            'commission_amount' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|max:40',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->saveBooking($data, optional($request->user())->id);
        return redirect()->route('hotel-management.channel-manager.index')->with('status', 'Channel booking captured successfully.');
    }

    public function bookingStatus(Request $request, int $id)
    {
        $data = $request->validate(['status' => 'required|string|max:40', 'remarks' => 'nullable|string|max:1000']);
        $this->service->bookingStatus($id, $data, optional($request->user())->id);
        return redirect()->route('hotel-management.channel-manager.index')->with('status', 'Channel booking status updated.');
    }
}
