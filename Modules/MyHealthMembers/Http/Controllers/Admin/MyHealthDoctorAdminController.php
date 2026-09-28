<?php

namespace Modules\MyHealthMembers\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthDoctor;
use Modules\MyHealthMembers\Services\Admin\MyHealthAdministrationService;

class MyHealthDoctorAdminController extends Controller
{
    public function index(MyHealthAdministrationService $service)
    {
        return view('myhealthmembers::admin.doctors.index', [
            'doctors' => $service->doctors(),
        ]);
    }

    public function show(MyHealthDoctor $doctor)
    {
        return view('myhealthmembers::admin.doctors.show', compact('doctor'));
    }
}
