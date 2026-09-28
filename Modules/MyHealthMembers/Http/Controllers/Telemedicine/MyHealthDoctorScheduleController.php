<?php

namespace Modules\MyHealthMembers\Http\Controllers\Telemedicine;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthDoctor;
use Modules\MyHealthMembers\Entities\MyHealthDoctorSchedule;
use Modules\MyHealthMembers\Services\MyHealthPermissionService;

class MyHealthDoctorScheduleController extends Controller
{
    public function index(MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_telemedicine'), 403);
        $schedules = MyHealthDoctorSchedule::query()->with('doctor')->orderByDesc('schedule_date')->orderBy('start_time')->paginate(25);
        return view('myhealthmembers::telemedicine.schedules.index', compact('schedules'));
    }

    public function create(MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_telemedicine'), 403);
        return view('myhealthmembers::telemedicine.schedules.create', [
            'doctors' => MyHealthDoctor::query()->where('status', 'active')->orderBy('doctor_name')->get(),
        ]);
    }

    public function store(Request $request, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_telemedicine'), 403);
        $data = $request->validate([
            'doctor_id' => ['required', 'integer'],
            'schedule_date' => ['required', 'date'],
            'start_time' => ['required'],
            'end_time' => ['required'],
            'slot_minutes' => ['nullable', 'integer', 'min:5'],
            'consultation_mode' => ['nullable', 'string'],
            'consultation_fee' => ['nullable', 'numeric'],
            'remarks' => ['nullable', 'string'],
        ]);
        $data['created_by'] = auth()->id();
        $data['status'] = 'available';
        MyHealthDoctorSchedule::create($data);
        return redirect()->route('myhealth.telemedicine.schedules.index')->with('status', __('myhealthmembers::lang.telemedicine_schedule_saved'));
    }
}
