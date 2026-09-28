<?php
namespace Modules\HRManager\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Services\HrSafeDataService;
class HrManagerController extends Controller
{
    protected HrSafeDataService $safe;
    public function __construct(HrSafeDataService $safe){ $this->safe=$safe; }
    public function dashboard(){ return view('hrmanager::dashboard.index',$this->payload()); }
    public function employees(Request $request){ $data=$this->payload(); $data['employees']=$this->employeeRows($request); return view('hrmanager::employees.index',$data); }
    public function setup(){ return view('hrmanager::setup.index',$this->payload()); }
    public function attendance(){ return view('hrmanager::attendance.index',$this->payload()); }
    public function faceAttendance(){ return view('hrmanager::face.index',$this->payload()); }
    public function leave(){ return view('hrmanager::leave.index',$this->payload()); }
    public function payroll(){ return view('hrmanager::payroll.index',$this->payload()); }
    public function employeeRecords(){ return view('hrmanager::employee_records.index',$this->payload()); }
    public function reports(){ return view('hrmanager::reports.index',$this->payload()); }
    private function payload(): array {
        $b=session('business.id');
        return [
            'stats'=>[
                'employees'=>$this->safe->count('hr_employees',$b),'active_employees'=>$this->safe->count('hr_employees',$b,['status'=>1]),
                'departments'=>$this->safe->count('hr_departments',$b),'designations'=>$this->safe->count('hr_designations',$b),'shifts'=>$this->safe->count('hr_shifts',$b),
                'present_today'=>$this->todayAttendance($b),'leave_requests'=>$this->safe->count('hr_leave_requests',$b),'pending_leave'=>$this->safe->count('hr_leave_requests',$b,['status'=>'pending']),
                'payroll_runs'=>$this->safe->count('hr_payroll_runs',$b),'payroll_total'=>$this->safe->sum('hr_payroll_runs',$b,'net_total'),
                'face_profiles'=>$this->safe->count('hr_face_profiles',$b),'face_devices'=>$this->safe->count('hr_face_devices',$b),'contracts'=>$this->safe->count('hr_employee_contracts',$b),'warnings'=>$this->safe->count('hr_employee_warnings',$b)
            ],
            'employees'=>$this->safe->rows('hr_employees',$b,25),'attendanceRows'=>$this->safe->rows('hr_attendance',$b,25),
            'faceAttempts'=>$this->safe->rows('hr_face_attendance_attempts',$b,25),'leaveRows'=>$this->safe->rows('hr_leave_requests',$b,25),
            'payrollRuns'=>$this->safe->rows('hr_payroll_runs',$b,25),'contracts'=>$this->safe->rows('hr_employee_contracts',$b,25),
        ];
    }
    private function todayAttendance($b): int {
        if (!$this->safe->hasTable('hr_attendance')) return 0;
        try { return DB::table('hr_attendance')->where('business_id',$b)->whereDate('attendance_date',now()->toDateString())->where('status','present')->count(); } catch(\Throwable $e){ return 0; }
    }
    private function employeeRows(Request $request) {
        $b=session('business.id');
        if (!$this->safe->hasTable('hr_employees')) return collect();
        return DB::table('hr_employees')->where('business_id',$b)
            ->when($request->search,function($q) use($request){$q->where(function($qq) use($request){$qq->where('employee_no','like','%'.$request->search.'%')->orWhere('full_name','like','%'.$request->search.'%')->orWhere('mobile','like','%'.$request->search.'%')->orWhere('nic_no','like','%'.$request->search.'%');});})
            ->orderByDesc('id')->paginate(request('per_page')==='all'?1000:(int)request('per_page',25));
    }
}
