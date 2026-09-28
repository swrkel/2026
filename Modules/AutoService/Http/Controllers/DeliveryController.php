<?php

namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Modules\AutoService\Services\AutoServiceDeliveryService;

class DeliveryController extends AutoServiceBaseController
{
    public function index(AutoServiceDeliveryService $service)
    {
        $jobs = $service->listReady($this->businessId())->paginate(25);
        return view('autoservice::deliveries.index', compact('jobs'));
    }

    public function store(Request $request, AutoServiceDeliveryService $service)
    {
        $data = $request->validate([
            'job_id' => 'required|integer',
            'invoice_confirmed' => 'nullable',
            'payment_confirmed' => 'nullable',
            'vehicle_handover_confirmed' => 'nullable',
            'customer_signature' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);
        $service->deliver((int)$data['job_id'], $data, $this->businessId(), $this->locationId());
        return redirect()->route('autoservice.deliveries.index')->with('status', 'Vehicle delivery saved successfully.');
    }
}
