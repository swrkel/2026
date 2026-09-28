<?php
namespace Modules\HRManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrLeaveApplication;
use Modules\HRManager\Services\HrEnterpriseLeaveService;

class HrEnterpriseLeaveController extends Controller
{
    protected HrEnterpriseLeaveService $service;
    public function __construct(HrEnterpriseLeaveService $service){ $this->service=$service; }

    public function index(Request $request)
    {
        $b=session('business.id');
        $employees=$this->rows('hr_employees',$b,200);
        $types=$this->rows('hr_leave_types',$b,100);
        $policies=$this->rows('hr_leave_policies',$b,25);
        $balances=$this->rows('hr_leave_balances',$b,25);
        $applications=$this->applications($request,$b);
        $approvals=$this->rows('hr_leave_approvals',$b,25);
        $calendar=$this->rows('hr_leave_calendar',$b,25);
        $holidays=$this->rows('hr_leave_holidays',$b,25);
        $stats=['employees'=>$this->count('hr_employees',$b),'leave_types'=>$this->count('hr_leave_types',$b),'policies'=>$this->count('hr_leave_policies',$b),'applications'=>$this->count('hr_leave_applications',$b),'pending'=>$this->count('hr_leave_applications',$b,['application_status'=>'pending']),'approved'=>$this->count('hr_leave_applications',$b,['application_status'=>'approved']),'rejected'=>$this->count('hr_leave_applications',$b,['application_status'=>'rejected']),'holidays'=>$this->count('hr_leave_holidays',$b)];
        return view('hrmanager::leave.enterprise', compact('employees','types','policies','balances','applications','approvals','calendar','holidays','stats'));
    }

    public function storeApplication(Request $request)
    {
        $request->validate(['employee_id'=>'required|integer','leave_type_id'=>'required|integer','from_date'=>'required|date','to_date'=>'required|date|after_or_equal:from_date']);
        $this->service->apply(['business_id'=>session('business.id'),'employee_id'=>$request->employee_id,'leave_type_id'=>$request->leave_type_id,'from_date'=>$request->from_date,'to_date'=>$request->to_date,'reason'=>$request->reason,'user_id'=>auth()->id()]);
        return back();
    }

    public function approve($id){ $app=HrLeaveApplication::where('business_id',session('business.id'))->findOrFail($id); $this->service->approve($app,auth()->id()); return back(); }
    public function reject(Request $request,$id){ $app=HrLeaveApplication::where('business_id',session('business.id'))->findOrFail($id); $this->service->reject($app,$request->reason ?? 'Rejected',auth()->id()); return back(); }

    private function hasTable($table){ try{return DB::getSchemaBuilder()->hasTable($table);}catch(\Throwable $e){return false;} }
    private function rows($table,$b,$limit){ return $this->hasTable($table)?DB::table($table)->where('business_id',$b)->orderByDesc('id')->limit($limit)->get():collect(); }
    private function count($table,$b,$where=[]){ if(!$this->hasTable($table))return 0; $q=DB::table($table)->where('business_id',$b); foreach($where as $k=>$v){$q->where($k,$v);} return $q->count(); }
    private function applications(Request $request,$b){ if(!$this->hasTable('hr_leave_applications'))return collect(); $q=DB::table('hr_leave_applications')->where('business_id',$b); if($request->search){$q->where('application_no','like','%'.$request->search.'%')->orWhere('employee_id','like','%'.$request->search.'%')->orWhere('application_status','like','%'.$request->search.'%');} if($request->date_from)$q->whereDate('from_date','>=',$request->date_from); if($request->date_to)$q->whereDate('to_date','<=',$request->date_to); return $q->orderByDesc('id')->paginate($request->per_page==='all'?1000:(int)$request->get('per_page',25)); }
}
