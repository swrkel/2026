<?php

namespace Modules\DistributionNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Models\DisnewDelivery;
use Modules\DistributionNew\Services\Deliveries\DisnewDeliveryService;

class DeliveryController extends Controller
{
    public function index(Request $request)
    {
        $deliveries = DisnewDelivery::where('business_id', $request->session()->get('business.id'))->latest()->paginate(25);
        return view('distributionnew::deliveries.index', compact('deliveries'));
    }

    public function show(DisnewDelivery $delivery)
    {
        return view('distributionnew::deliveries.show', compact('delivery'));
    }

    public function markDelivered(Request $request, DisnewDelivery $delivery, DisnewDeliveryService $service)
    {
        $service->markDelivered($delivery, $request->only(['received_by','receiver_mobile','proof_note','gps_lat','gps_lng']));
        return redirect()->back()->with('status', __('distributionnew::messages.delivery_completed'));
    }
}
