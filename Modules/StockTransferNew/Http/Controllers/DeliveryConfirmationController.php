<?php

namespace Modules\StockTransferNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Services\DeliveryConfirmationService;

class DeliveryConfirmationController extends Controller
{
    protected DeliveryConfirmationService $service;

    public function __construct(DeliveryConfirmationService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['business_id', 'location_id', 'store_id', 'status', 'date_from', 'date_to', 'q']);
        $records = $this->service->list($filters);
        return view('stocktransfernew::delivery.index', compact('records', 'filters'));
    }

    public function show($id)
    {
        $delivery = $this->service->find((int) $id);
        abort_if(!$delivery, 404);
        return view('stocktransfernew::delivery.show', compact('delivery'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'transfer_id' => 'required|integer',
            'delivered_at' => 'nullable|date',
            'received_by' => 'nullable|string|max:191',
            'receiver_mobile' => 'nullable|string|max:50',
            'condition_status' => 'required|string|max:50',
            'remarks' => 'nullable|string',
            'signature_data' => 'nullable|string',
            'photo_reference' => 'nullable|string|max:255',
        ]);

        $delivery = $this->service->confirm($data, (int) auth()->id());
        return redirect()->route('stock-transfer-new.delivery.show', $delivery->id)
            ->with('status', __('stocktransfernew::delivery.confirmed'));
    }

    public function damage(Request $request, $id)
    {
        $data = $request->validate([
            'product_id' => 'required|integer',
            'qty' => 'required|numeric|min:0.001',
            'damage_type' => 'required|string|max:100',
            'estimated_value' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string',
        ]);

        $this->service->recordDamage((int) $id, $data, (int) auth()->id());
        return back()->with('status', __('stocktransfernew::delivery.damage_recorded'));
    }
}
