<?php

namespace Modules\HotelManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HotelManagement\Services\OnlineBookingService;

class OnlineBookingController extends Controller
{
    protected OnlineBookingService $service;

    public function __construct(OnlineBookingService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $onlineBooking = $this->service->dashboard($request->all());
        return view('hotelmanagement::online_booking.index', compact('onlineBooking'));
    }

    public function promotion(Request $request)
    {
        $data = $request->validate([
            'promo_code' => 'required|string|max:80',
            'promo_name' => 'required|string|max:160',
            'discount_type' => 'required|string|max:30',
            'discount_value' => 'required|numeric|min:0',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'is_active' => 'nullable',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->savePromotion($data, optional($request->user())->id);
        return redirect()->route('hotel-management.online-booking.index')->with('status', 'Online booking promotion saved successfully.');
    }

    public function booking(Request $request)
    {
        $data = $request->validate([
            'booking_source' => 'nullable|string|max:80',
            'guest_name' => 'required|string|max:160',
            'guest_mobile' => 'nullable|string|max:50',
            'guest_email' => 'nullable|email|max:160',
            'arrival_date' => 'required|date',
            'departure_date' => 'required|date|after:arrival_date',
            'room_type_id' => 'nullable|integer',
            'rate_plan_id' => 'nullable|integer',
            'rooms' => 'required|integer|min:1',
            'adults' => 'nullable|integer|min:0',
            'children' => 'nullable|integer|min:0',
            'coupon_code' => 'nullable|string|max:80',
            'gross_amount' => 'required|numeric|min:0',
            'advance_amount' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|max:40',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->saveBooking($data, optional($request->user())->id);
        return redirect()->route('hotel-management.online-booking.index')->with('status', 'Online booking saved successfully.');
    }

    public function modify(Request $request, int $id)
    {
        $data = $request->validate([
            'arrival_date' => 'nullable|date',
            'departure_date' => 'nullable|date|after:arrival_date',
            'rooms' => 'nullable|integer|min:1',
            'status' => 'nullable|string|max:40',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->modifyBooking($id, $data, optional($request->user())->id);
        return redirect()->route('hotel-management.online-booking.index')->with('status', 'Online booking modified successfully.');
    }

    public function cancel(Request $request, int $id)
    {
        $data = $request->validate(['cancel_reason' => 'nullable|string|max:1000']);
        $this->service->cancelBooking($id, $data, optional($request->user())->id);
        return redirect()->route('hotel-management.online-booking.index')->with('status', 'Online booking cancelled successfully.');
    }

    public function refund(Request $request, int $id)
    {
        $data = $request->validate(['refund_amount' => 'required|numeric|min:0', 'refund_note' => 'nullable|string|max:1000']);
        $this->service->refundBooking($id, $data, optional($request->user())->id);
        return redirect()->route('hotel-management.online-booking.index')->with('status', 'Online booking refund recorded successfully.');
    }
}
