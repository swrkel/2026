<?php

namespace Modules\MyHealthMembers\Http\Controllers\Telemedicine;

use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthDoctorSchedule;
use Modules\MyHealthMembers\Entities\MyHealthTelemedicineAppointment;
use Modules\MyHealthMembers\Services\MyHealthPermissionService;

class MyHealthTelemedicineDashboardController extends Controller
{
    public function index(MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_telemedicine'), 403);

        return view('myhealthmembers::telemedicine.dashboard', [
            'todayAppointments' => MyHealthTelemedicineAppointment::query()->whereDate('appointment_date', date('Y-m-d'))->count(),
            'waitingAppointments' => MyHealthTelemedicineAppointment::query()->where('status', 'waiting')->count(),
            'bookedAppointments' => MyHealthTelemedicineAppointment::query()->where('status', 'booked')->count(),
            'availableSchedules' => MyHealthDoctorSchedule::query()->where('status', 'available')->whereDate('schedule_date', '>=', date('Y-m-d'))->count(),
        ]);
    }
}
