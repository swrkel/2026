<?php

namespace Modules\HotelManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HotelManagement\Services\MinibarService;
use Throwable;

class MinibarController extends Controller
{
    protected MinibarService $service;

    public function __construct(MinibarService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $minibar = $this->service->dashboard();
        return view('hotelmanagement::minibar.index', compact('minibar'));
    }

    public function item(Request $request)
    {
        $data = $request->validate([
            'item_code' => 'required|string|max:80',
            'item_name' => 'required|string|max:150',
            'category' => 'nullable|string|max:80',
            'unit' => 'nullable|string|max:30',
            'selling_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'current_stock' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'is_active' => 'nullable',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->saveItem($data, optional($request->user())->id);
        return redirect()->route('hotel-management.minibar.index')->with('status', 'Mini bar item saved successfully.');
    }

    public function consumption(Request $request)
    {
        $data = $request->validate([
            'consumption_no' => 'nullable|string|max:80',
            'room_id' => 'nullable|integer',
            'room_no' => 'nullable|string|max:30',
            'reservation_id' => 'nullable|integer',
            'folio_id' => 'nullable|integer',
            'guest_name' => 'nullable|string|max:150',
            'item_id' => 'required|integer',
            'consumption_date' => 'nullable|date',
            'qty' => 'required|numeric|min:0.0001',
            'unit_price' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|max:30',
            'remarks' => 'nullable|string|max:1000',
        ]);
        try {
            $this->service->saveConsumption($data, optional($request->user())->id);
            return redirect()->route('hotel-management.minibar.index')->with('status', 'Mini bar consumption saved successfully.');
        } catch (Throwable $e) {
            return redirect()->route('hotel-management.minibar.index')->with('error', $e->getMessage());
        }
    }

    public function status(Request $request, int $id)
    {
        $request->validate(['status' => 'required|string|max:30']);
        $this->service->updateStatus($id, $request->input('status'), optional($request->user())->id);
        return redirect()->route('hotel-management.minibar.index')->with('status', 'Mini bar status updated.');
    }

    public function postToFolio(Request $request, int $id)
    {
        $this->service->postToFolio($id, optional($request->user())->id);
        return redirect()->route('hotel-management.minibar.index')->with('status', 'Mini bar consumption posted to folio.');
    }
}
