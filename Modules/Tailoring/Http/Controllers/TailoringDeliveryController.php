<?php

namespace Modules\Tailoring\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tailoring\Entities\TailoringDeliverySchedule;

class TailoringDeliveryController extends Controller
{
    public function index()
    {
        $deliveries = TailoringDeliverySchedule::latest('delivery_date')->paginate(25);
        return view('tailoring::delivery.index', compact('deliveries'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'order_id' => 'nullable|integer',
            'job_card_id' => 'nullable|integer',
            'delivery_date' => 'required|date',
            'delivery_time' => 'nullable',
            'delivery_status' => 'nullable|string|max:30',
            'balance_to_collect' => 'nullable|numeric',
            'delivery_notes' => 'nullable|string',
        ]);
        $data['business_id'] = session('business.id');
        $data['location_id'] = $request->input('location_id');
        TailoringDeliverySchedule::create($data);
        return redirect()->back()->with('status', 'Delivery schedule saved successfully.');
    }
}
