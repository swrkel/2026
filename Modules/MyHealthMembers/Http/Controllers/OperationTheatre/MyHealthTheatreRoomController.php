<?php

namespace Modules\MyHealthMembers\Http\Controllers\OperationTheatre;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthOperationTheatreRoom;

class MyHealthTheatreRoomController extends Controller
{
    public function index()
    {
        return view('myhealthmembers::operation_theatre.theatres.index', [
            'rooms' => MyHealthOperationTheatreRoom::orderBy('room_code')->paginate(25),
        ]);
    }

    public function create()
    {
        return view('myhealthmembers::operation_theatre.theatres.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_code' => 'required|string|max:50',
            'room_name' => 'required|string|max:191',
            'room_type' => 'nullable|string|max:100',
            'floor' => 'nullable|string|max:100',
            'status' => 'nullable|string|max:50',
            'equipment_notes' => 'nullable|string',
        ]);

        $data['business_id'] = session('business.id') ?? session('user.business_id');
        $data['location_id'] = session('business_location_id') ?? null;
        $data['created_by'] = auth()->id();

        MyHealthOperationTheatreRoom::create($data);

        return redirect()->route('myhealth.operation_theatre.theatres.index')->with('status', 'Theatre room saved successfully.');
    }
}
