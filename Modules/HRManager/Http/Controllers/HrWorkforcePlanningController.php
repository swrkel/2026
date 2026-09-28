<?php
namespace Modules\HRManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Services\HrWorkforcePlanningService;

class HrWorkforcePlanningController extends Controller
{
    protected HrWorkforcePlanningService $service;
    public function __construct(HrWorkforcePlanningService $service){ $this->service=$service; }

    public function index(Request $request)
    {
        $b=session('business.id');
        $plans=$this->planRows($request,$b);
        $lines=$this->rows('hr_workforce_plan_lines',$b,25);
        $budgets=$this->rows('hr_headcount_budgets',$b,25);
        $forecasts=$this->rows('hr_workforce_forecasts',$b,25);
        $scenarios=$this->rows('hr_workforce_scenarios',$b,25);
        $risks=$this->rows('hr_workforce_risk_register',$b,25);
        $stats=[
            'employees'=>$this->count('hr_employees',$b),
            'plans'=>$this->count('hr_workforce_plans',$b),
            'budgets'=>$this->count('hr_headcount_budgets',$b),
            'forecasts'=>$this->count('hr_workforce_forecasts',$b),
            'scenarios'=>$this->count('hr_workforce_scenarios',$b),
            'risks'=>$this->count('hr_workforce_risk_register',$b),
            'open_risks'=>$this->count('hr_workforce_risk_register',$b,['risk_status'=>'open']),
            'approved_plans'=>$this->count('hr_workforce_plans',$b,['approval_status'=>'approved']),
        ];
        return view('hrmanager::workforce.index', compact('plans','lines','budgets','forecasts','scenarios','risks','stats'));
    }

    public function storePlan(Request $request)
    {
        $request->validate(['plan_name'=>'required|string|max:180','plan_year'=>'required']);
        $this->service->createPlan([
            'business_id'=>session('business.id'),
            'plan_name'=>$request->plan_name,
            'plan_year'=>$request->plan_year,
            'department_id'=>$request->department_id,
            'location_id'=>$request->location_id,
            'user_id'=>auth()->id(),
        ]);
        return back();
    }

    private function hasTable($t){ try{return DB::getSchemaBuilder()->hasTable($t);}catch(\Throwable $e){return false;} }
    private function rows($t,$b,$l){ return $this->hasTable($t)?DB::table($t)->where('business_id',$b)->orderByDesc('id')->limit($l)->get():collect(); }
    private function count($t,$b,$w=[]){ if(!$this->hasTable($t)) return 0; $q=DB::table($t)->where('business_id',$b); foreach($w as $k=>$v){$q->where($k,$v);} return $q->count(); }
    private function planRows(Request $r,$b){ if(!$this->hasTable('hr_workforce_plans')) return collect(); $q=DB::table('hr_workforce_plans')->where('business_id',$b); if($r->search){$q->where('plan_no','like','%'.$r->search.'%')->orWhere('plan_name','like','%'.$r->search.'%')->orWhere('plan_status','like','%'.$r->search.'%');} return $q->orderByDesc('id')->paginate($r->per_page==='all'?1000:(int)$r->get('per_page',25)); }
}
