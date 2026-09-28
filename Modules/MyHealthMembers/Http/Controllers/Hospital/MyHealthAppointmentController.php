<?php

namespace Modules\MyHealthMembers\Http\Controllers\Hospital;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthAppointment;
use Modules\MyHealthMembers\Entities\MyHealthConsultationRoom;
use Modules\MyHealthMembers\Entities\MyHealthDoctor;
use Modules\MyHealthMembers\Entities\MyHealthHospitalDepartment;
use Modules\MyHealthMembers\Entities\MyHealthMember;
use Modules\MyHealthMembers\Services\Hospital\MyHealthAppointmentService;

class MyHealthAppointmentController extends Controller
{
    public function index(Request $request)
    {
        $appointments = MyHealthAppointment::with(['member', 'doctor', 'department', 'room'])
            ->when($request->filled('date'), fn ($q) => $q->whereDate('appointment_date', $request->date))
            ->when(!$request->filled('date'), fn ($q) => $q->whereDate('appointment_date', now()->toDateString()))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy('appointment_date', 'desc')
            ->orderBy('queue_no')
            ->paginate(25);

        return view('myhealthmembers::hospital.appointments.index', compact('appointments'));
    }

    public function create()
    {
        return view('myhealthmembers::hospital.appointments.create', [
            'members' => MyHealthMember::orderBy('name')->limit(100)->get(),
            'doctors' => MyHealthDoctor::orderBy('name')->get(),
            'departments' => MyHealthHospitalDepartment::where('is_active', true)->orderBy('name')->get(),
            'rooms' => MyHealthConsultationRoom::where('is_active', true)->orderBy('room_name')->get(),
        ]);
    }

    public function store(Request $request, MyHealthAppointmentService $service)
    {
        $data = $request->validate([
            'member_id' => 'required|integer',
            'doctor_id' => 'nullable|integer',
            'department_id' => 'nullable|integer',
            'room_id' => 'nullable|integer',
            'appointment_date' => 'required|date',
            'appointment_time' => 'nullable',
            'visit_type' => 'nullable|string|max:30',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $appointment = $service->create($data);
        return redirect()->route('myhealth.hospital.appointments.index')->with('status', 'Appointment created. Token No: ' . $appointment->token_no);
    }

    public function status(MyHealthAppointment $appointment, Request $request, MyHealthAppointmentService $service)
    {
        $request->validate(['status' => 'required|string|max:30']);
        $service->updateStatus($appointment, $request->status);
        return back()->with('status', 'Appointment status updated.');
    }
}
