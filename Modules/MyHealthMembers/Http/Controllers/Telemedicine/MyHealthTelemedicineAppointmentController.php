<?php

namespace Modules\MyHealthMembers\Http\Controllers\Telemedicine;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthDoctor;
use Modules\MyHealthMembers\Entities\MyHealthDoctorSchedule;
use Modules\MyHealthMembers\Entities\MyHealthMember;
use Modules\MyHealthMembers\Entities\MyHealthTelemedicineAppointment;
use Modules\MyHealthMembers\Services\MyHealthAppointmentNumberService;
use Modules\MyHealthMembers\Services\MyHealthPermissionService;
use Modules\MyHealthMembers\Services\MyHealthTelemedicineSessionService;

class MyHealthTelemedicineAppointmentController extends Controller
{
    public function index(Request $request, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_telemedicine'), 403);
        $query = MyHealthTelemedicineAppointment::query()->with(['member', 'doctor', 'session']);
        if ($search = trim((string) $request->input('search'))) {
            $query->where('appointment_no', 'like', "%{$search}%")
                ->orWhereHas('member', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('mobile', 'like', "%{$search}%"));
        }
        $appointments = $query->latest('id')->paginate(25);
        return view('myhealthmembers::telemedicine.appointments.index', compact('appointments'));
    }

    public function create(MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_telemedicine'), 403);
        return view('myhealthmembers::telemedicine.appointments.create', [
            'members' => MyHealthMember::query()->where('status', 'active')->orderBy('name')->get(),
            'doctors' => MyHealthDoctor::query()->where('status', 'active')->orderBy('doctor_name')->get(),
            'schedules' => MyHealthDoctorSchedule::query()->with('doctor')->where('status', 'available')->whereDate('schedule_date', '>=', date('Y-m-d'))->orderBy('schedule_date')->get(),
        ]);
    }

    public function store(Request $request, MyHealthAppointmentNumberService $numberService, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_telemedicine'), 403);
        $data = $request->validate([
            'member_id' => ['required', 'integer'],
            'doctor_id' => ['required', 'integer'],
            'schedule_id' => ['nullable', 'integer'],
            'appointment_date' => ['required', 'date'],
            'appointment_time' => ['required'],
            'consultation_mode' => ['nullable', 'string'],
            'consultation_fee' => ['nullable', 'numeric'],
            'reason' => ['nullable', 'string'],
        ]);
        $data['appointment_no'] = $numberService->nextNumber();
        $data['status'] = 'booked';
        $data['consent_status'] = 'pending';
        $data['created_by'] = auth()->id();
        MyHealthTelemedicineAppointment::create($data);
        return redirect()->route('myhealth.telemedicine.appointments.index')->with('status', __('myhealthmembers::lang.telemedicine_appointment_saved'));
    }

    public function show(MyHealthTelemedicineAppointment $appointment, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_telemedicine'), 403);
        $appointment->load(['member', 'doctor', 'schedule', 'session']);
        return view('myhealthmembers::telemedicine.appointments.show', compact('appointment'));
    }

    public function openSession(MyHealthTelemedicineAppointment $appointment, MyHealthTelemedicineSessionService $sessionService, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_telemedicine'), 403);
        $sessionService->openSession($appointment);
        return redirect()->route('myhealth.telemedicine.appointments.show', $appointment->id)->with('status', __('myhealthmembers::lang.telemedicine_session_opened'));
    }

    public function waitingRoom(MyHealthTelemedicineAppointment $appointment, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_telemedicine'), 403);
        $appointment->load(['member', 'doctor', 'session']);
        return view('myhealthmembers::telemedicine.waiting_room', compact('appointment'));
    }
}
