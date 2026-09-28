<?php

namespace Modules\HRManager\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\HRManager\Models\HrDepartment;
use Modules\HRManager\Models\HrDesignation;
use Modules\HRManager\Models\HrShift;
use Modules\HRManager\Models\HrHoliday;
use Modules\HRManager\Models\HrWeeklyOff;
use Modules\HRManager\Services\HrSetupService;

class HrSetupController extends Controller
{
    protected HrSetupService $service;

    public function __construct(HrSetupService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return view('hrmanager::setup.index', [
            'counts' => $this->service->dashboardCounts(),
            'departments' => $this->service->departments(),
            'designations' => $this->service->designations(),
            'shifts' => $this->service->shifts(),
            'holidays' => $this->service->holidays(),
            'weeklyOffs' => $this->service->weeklyOffs(),
            'days' => [1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',5=>'Friday',6=>'Saturday',7=>'Sunday'],
        ]);
    }

    public function storeDepartment(Request $request)
    {
        $data = $request->validate(['code'=>'nullable|string|max:30','name'=>'required|string|max:120','description'=>'nullable|string|max:500','is_active'=>'nullable|boolean']);
        $data['is_active'] = $request->boolean('is_active', true);
        $this->service->saveDepartment($data);
        return back()->with('status', 'Department saved successfully.');
    }

    public function updateDepartment(Request $request, HrDepartment $department)
    {
        $data = $request->validate(['code'=>'nullable|string|max:30','name'=>'required|string|max:120','description'=>'nullable|string|max:500','is_active'=>'nullable|boolean']);
        $data['is_active'] = $request->boolean('is_active');
        $this->service->saveDepartment($data, $department);
        return back()->with('status', 'Department updated successfully.');
    }

    public function storeDesignation(Request $request)
    {
        $data = $request->validate(['department_id'=>'nullable|integer','code'=>'nullable|string|max:30','name'=>'required|string|max:120','grade_level'=>'nullable|string|max:50','description'=>'nullable|string|max:500','is_active'=>'nullable|boolean']);
        $data['is_active'] = $request->boolean('is_active', true);
        $this->service->saveDesignation($data);
        return back()->with('status', 'Designation saved successfully.');
    }

    public function updateDesignation(Request $request, HrDesignation $designation)
    {
        $data = $request->validate(['department_id'=>'nullable|integer','code'=>'nullable|string|max:30','name'=>'required|string|max:120','grade_level'=>'nullable|string|max:50','description'=>'nullable|string|max:500','is_active'=>'nullable|boolean']);
        $data['is_active'] = $request->boolean('is_active');
        $this->service->saveDesignation($data, $designation);
        return back()->with('status', 'Designation updated successfully.');
    }

    public function storeShift(Request $request)
    {
        $data = $request->validate(['code'=>'nullable|string|max:30','name'=>'required|string|max:120','start_time'=>'required','end_time'=>'required','break_minutes'=>'nullable|integer|min:0','grace_in_minutes'=>'nullable|integer|min:0','grace_out_minutes'=>'nullable|integer|min:0','overtime_after_minutes'=>'nullable|integer|min:0','is_night_shift'=>'nullable|boolean','is_active'=>'nullable|boolean']);
        $data['is_active'] = $request->boolean('is_active', true);
        $this->service->saveShift($data);
        return back()->with('status', 'Shift saved successfully.');
    }

    public function updateShift(Request $request, HrShift $shift)
    {
        $data = $request->validate(['code'=>'nullable|string|max:30','name'=>'required|string|max:120','start_time'=>'required','end_time'=>'required','break_minutes'=>'nullable|integer|min:0','grace_in_minutes'=>'nullable|integer|min:0','grace_out_minutes'=>'nullable|integer|min:0','overtime_after_minutes'=>'nullable|integer|min:0','is_night_shift'=>'nullable|boolean','is_active'=>'nullable|boolean']);
        $data['is_active'] = $request->boolean('is_active');
        $this->service->saveShift($data, $shift);
        return back()->with('status', 'Shift updated successfully.');
    }

    public function storeHoliday(Request $request)
    {
        $data = $request->validate(['holiday_date'=>'required|date','name'=>'required|string|max:150','type'=>'nullable|string|max:50','is_paid'=>'nullable|boolean','notes'=>'nullable|string|max:500','is_active'=>'nullable|boolean']);
        $data['is_active'] = $request->boolean('is_active', true);
        $this->service->saveHoliday($data);
        return back()->with('status', 'Holiday saved successfully.');
    }

    public function storeWeeklyOff(Request $request)
    {
        $data = $request->validate(['name'=>'required|string|max:120','day_of_week'=>'required|integer|min:1|max:7','is_active'=>'nullable|boolean']);
        $data['is_active'] = $request->boolean('is_active', true);
        $this->service->saveWeeklyOff($data);
        return back()->with('status', 'Weekly off saved successfully.');
    }
}
