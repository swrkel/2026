<?php

namespace Modules\MyHealthMembers\Http\Controllers\Hospital;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthConsultationRoom;
use Modules\MyHealthMembers\Entities\MyHealthDoctor;
use Modules\MyHealthMembers\Entities\MyHealthDoctorSessionSchedule;
use Modules\MyHealthMembers\Entities\MyHealthHospitalDepartment;

class MyHealthScheduleController extends Controller
{
    public function index()
    {
        $schedules = MyHealthDoctorSessionSchedule::with(['doctor', 'department', 'room'])
            ->orderBy('doctor_id')
            ->orderBy('day_of_week')
            ->get();

        return view('myhealthmembers::hospital.schedules.index', compact('schedules'));
    }

    public function create()
    {
        return view('myhealthmembers::hospital.schedules.create', [
            'doctors' => MyHealthDoctor::orderBy('name')->get(),
            'departments' => MyHealthHospitalDepartment::where('is_active', true)->orderBy('name')->get(),
            'rooms' => MyHealthConsultationRoom::where('is_active', true)->orderBy('room_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'doctor_id' => 'required|integer',
            'department_id' => 'nullable|integer',
            'room_id' => 'nullable|integer',
            'day_of_week' => 'required|string|max:20',
            'start_time' => 'required',
            'end_time' => 'required',
            'consultation_duration_minutes' => 'nullable|integer|min:1',
            'maximum_patients' => 'nullable|integer|min:1',
            'break_start_time' => 'nullable',
            'break_end_time' => 'nullable',
        ]);

        MyHealthDoctorSessionSchedule::create($data);
        return redirect()->route('myhealth.hospital.schedules.index')->with('status', 'Doctor schedule saved.');
    }
}
