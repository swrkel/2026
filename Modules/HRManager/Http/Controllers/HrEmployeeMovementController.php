<?php
namespace Modules\HRManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Services\HrEmployeeMovementService;

class HrEmployeeMovementController extends Controller
{
    protected HrEmployeeMovementService $service;
    public function __construct(HrEmployeeMovementService $service){ $this->service=$service; }

    public function index(Request $request)
    {
        $b=session('business.id');
        $employees=$this->rows('hr_employees',$b,300);
        $movements=$this->movementRows($request,$b);
        $approvals=$this->rows('hr_employee_movement_approvals',$b,25);
        $history=$this->rows('hr_employee_movement_history',$b,25);
        $salaryRevisions=$this->rows('hr_employee_salary_revision_history',$b,25);
        $probations=$this->rows('hr_employee_probation_actions',$b,25);
        $events=$this->rows('hr_employee_lifecycle_events',$b,25);

        $stats=[
            'employees'=>$this->count('hr_employees',$b),
            'movements'=>$this->count('hr_employee_movements',$b),
            'pending'=>$this->count('hr_employee_movements',$b,['approval_status'=>'pending']),
            'approved'=>$this->count('hr_employee_movements',$b,['approval_status'=>'approved']),
            'salary_revisions'=>$this->count('hr_employee_salary_revision_history',$b),
            'probations'=>$this->count('hr_employee_probation_actions',$b),
            'events'=>$this->count('hr_employee_lifecycle_events',$b),
            'history'=>$this->count('hr_employee_movement_history',$b),
        ];

        return view('hrmanager::movements.index', compact('employees','movements','approvals','history','salaryRevisions','probations','events','stats'));
    }

    public function storeMovement(Request $request)
    {
        $request->validate(['employee_id'=>'required|integer','movement_type'=>'required|string|max:100']);
        $this->service->createMovement([
            'business_id'=>session('business.id'),
            'employee_id'=>$request->employee_id,
            'movement_type'=>$request->movement_type,
            'effective_date'=>$request->effective_date,
            'new_department_id'=>$request->new_department_id,
            'new_designation_id'=>$request->new_designation_id,
            'new_location_id'=>$request->new_location_id,
            'new_reporting_manager_id'=>$request->new_reporting_manager_id,
            'new_salary'=>$request->new_salary ?? 0,
            'reason'=>$request->reason,
            'user_id'=>auth()->id(),
        ]);
        return back();
    }

    private function hasTable($t){ try{return DB::getSchemaBuilder()->hasTable($t);}catch(\Throwable $e){return false;} }
    private function rows($t,$b,$l){ return $this->hasTable($t)?DB::table($t)->where('business_id',$b)->orderByDesc('id')->limit($l)->get():collect(); }
    private function count($t,$b,$w=[]){ if(!$this->hasTable($t)) return 0; $q=DB::table($t)->where('business_id',$b); foreach($w as $k=>$v){$q->where($k,$v);} return $q->count(); }
    private function movementRows(Request $r,$b){ if(!$this->hasTable('hr_employee_movements')) return collect(); $q=DB::table('hr_employee_movements')->where('business_id',$b); if($r->search){$q->where('movement_no','like','%'.$r->search.'%')->orWhere('movement_type','like','%'.$r->search.'%')->orWhere('movement_status','like','%'.$r->search.'%');} return $q->orderByDesc('id')->paginate($r->per_page==='all'?1000:(int)$r->get('per_page',25)); }
}
