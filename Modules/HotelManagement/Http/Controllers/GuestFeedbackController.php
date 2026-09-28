<?php

namespace Modules\HotelManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HotelManagement\Services\GuestFeedbackService;

class GuestFeedbackController extends Controller
{
    protected GuestFeedbackService $service;

    public function __construct(GuestFeedbackService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $feedback = $this->service->dashboard();
        return view('hotelmanagement::feedback.index', compact('feedback'));
    }

    public function question(Request $request)
    {
        $data = $request->validate([
            'question' => 'required|string|max:255',
            'category' => 'nullable|string|max:60',
            'rating_scale' => 'nullable|integer|min:1|max:10',
            'display_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable',
        ]);
        $this->service->saveQuestion($data, optional($request->user())->id);
        return redirect()->route('hotel-management.feedback.index')->with('status', 'Hotel feedback question saved successfully.');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'guest_id' => 'nullable|integer',
            'reservation_id' => 'nullable|integer',
            'folio_id' => 'nullable|integer',
            'feedback_date' => 'nullable|date',
            'source' => 'nullable|string|max:60',
            'overall_rating' => 'required|numeric|min:0|max:10',
            'room_rating' => 'nullable|numeric|min:0|max:10',
            'service_rating' => 'nullable|numeric|min:0|max:10',
            'food_rating' => 'nullable|numeric|min:0|max:10',
            'cleanliness_rating' => 'nullable|numeric|min:0|max:10',
            'comments' => 'nullable|string|max:2000',
        ]);
        $this->service->saveFeedback($data, optional($request->user())->id);
        return redirect()->route('hotel-management.feedback.index')->with('status', 'Guest feedback recorded successfully.');
    }

    public function status(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => 'required|string|max:30',
            'resolution_note' => 'nullable|string|max:1000',
        ]);
        $this->service->updateStatus($id, $data, optional($request->user())->id);
        return redirect()->route('hotel-management.feedback.index')->with('status', 'Guest feedback status updated successfully.');
    }
}
