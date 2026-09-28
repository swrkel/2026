<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\POS\Entities\POSDevice;

class DeviceController extends Controller
{
    public function index() { return view('pos::devices.index', ['devices' => POSDevice::latest()->paginate(25)]); }
    public function create() { return view('pos::devices.create'); }
    public function store(Request $request) { POSDevice::create($request->all()); return redirect()->route('pos.devices.index')->with('status', __('pos::messages.saved_successfully')); }
    public function edit(POSDevice $device) { return view('pos::devices.edit', compact('device')); }
    public function update(Request $request, POSDevice $device) { $device->update($request->all()); return redirect()->route('pos.devices.index')->with('status', __('pos::messages.updated_successfully')); }
    public function destroy(POSDevice $device) { $device->delete(); return response()->json(['success' => true]); }
}
