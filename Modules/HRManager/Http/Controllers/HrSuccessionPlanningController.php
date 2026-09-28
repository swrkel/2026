<?php
namespace Modules\HRManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Services\HrSuccessionPlanningService;

class HrSuccessionPlanningController extends Controller
{
    protected HrSuccessionPlanningService $service;
    public function __construct(HrSuccessionPlanningService $service){ $this->service=$service; }

    public function index(Request $request)
    {
        $b=session('business.id');
        $employees=$this->rows('hr_employees',$b,200);
        $roles=$this->roleRows($request,$b);
        $pools=$this->rows('hr_succession_candidate_pools',$b,25);
        $candidates=$this->rows('hr_succession_candidates',$b,25);
        $plans=$this->rows('hr_succession_development_plans',$b,25);
        $assessments=$this->rows('hr_succession_assessments',$b,25);
        $matrix=$this->rows('hr_succession_talent_matrix',$b,25);
        $risks=$this->rows('hr_succession_risk_register',$b,25);
        $stats=[
            'employees'=>$this->count('hr_employees',$b),
            'roles'=>$this->count('hr_succession_critical_roles',$b),
            'pools'=>$this->count('hr_succession_candidate_pools',$b),
            'candidates'=>$this->count('hr_succession_candidates',$b),
            'plans'=>$this->count('hr_succession_development_plans',$b),
            'assessments'=>$this->count('hr_succession_assessments',$b),
            'risks'=>$this->count('hr_succession_risk_register',$b),
            'open_risks'=>$this->count('hr_succession_risk_register',$b,['risk_status'=>'open']),
        ];
        return view('hrmanager::succession.index', compact('employees','roles','pools','candidates','plans','assessments','matrix','risks','stats'));
    }

    public function storeRole(Request $request)
    {
        $request->validate(['role_title'=>'required|string|max:180']);
        $this->service->createCriticalRole([
            'business_id'=>session('business.id'),
            'role_title'=>$request->role_title,
            'department_id'=>$request->department_id,
            'designation_id'=>$request->designation_id,
            'current_employee_id'=>$request->current_employee_id,
            'criticality_level'=>$request->criticality_level ?? 'high',
            'vacancy_risk'=>$request->vacancy_risk ?? 'medium',
            'impact_summary'=>$request->impact_summary,
            'user_id'=>auth()->id(),
        ]);
        return back();
    }

    public function nominateCandidate(Request $request)
    {
        $request->validate(['critical_role_id'=>'required|integer','employee_id'=>'required|integer']);
        $this->service->nominateCandidate([
            'business_id'=>session('business.id'),
            'critical_role_id'=>$request->critical_role_id,
            'employee_id'=>$request->employee_id,
            'readiness_level'=>$request->readiness_level ?? 'medium_term',
            'readiness_percent'=>$request->readiness_percent ?? 0,
            'risk_of_loss'=>$request->risk_of_loss ?? 'medium',
            'user_id'=>auth()->id(),
        ]);
        return back();
    }

    private function hasTable($t){ try{return DB::getSchemaBuilder()->hasTable($t);}catch(\Throwable $e){return false;} }
    private function rows($t,$b,$l){ return $this->hasTable($t)?DB::table($t)->where('business_id',$b)->orderByDesc('id')->limit($l)->get():collect(); }
    private function count($t,$b,$w=[]){ if(!$this->hasTable($t)) return 0; $q=DB::table($t)->where('business_id',$b); foreach($w as $k=>$v){$q->where($k,$v);} return $q->count(); }
    private function roleRows(Request $r,$b){ if(!$this->hasTable('hr_succession_critical_roles')) return collect(); $q=DB::table('hr_succession_critical_roles')->where('business_id',$b); if($r->search){$q->where('role_no','like','%'.$r->search.'%')->orWhere('role_title','like','%'.$r->search.'%')->orWhere('status','like','%'.$r->search.'%');} return $q->orderByDesc('id')->paginate($r->per_page==='all'?1000:(int)$r->get('per_page',25)); }
}
