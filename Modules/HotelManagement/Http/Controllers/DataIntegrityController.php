<?php

namespace Modules\HotelManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HotelManagement\Services\DataIntegrityService;

class DataIntegrityController extends Controller
{
    protected DataIntegrityService $service;

    public function __construct(DataIntegrityService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $integrity = $this->service->dashboard();
        return view('hotelmanagement::data_integrity.index', compact('integrity'));
    }

    public function snapshot(Request $request)
    {
        $this->service->saveSnapshot(optional($request->user())->id);
        return redirect()->route('hotel-management.data-integrity.index')->with('status', 'Data integrity snapshot saved successfully.');
    }
}
