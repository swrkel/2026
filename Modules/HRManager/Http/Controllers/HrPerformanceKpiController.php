<?php
namespace Modules\HRManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Services\HrPerformanceKpiService;

class HrPerformanceKpiController extends Controller
{
    protected HrPerformanceKpiService $service;
    public function __construct(HrPerformanceKpiService $service){ $this->service=$service; }

    public function index(Request $request)
    {
        $b=session('business.id');
        $employees=$this->rows('hr_employees',$b,200);
        $categories=$this->rows('hr_kpi_categories',$b,25);
        $kpis=$this->rows('hr_kpi_library',$b,100);
        $cycles=$this->rows('hr_performance_cycles_kpi',$b,100);
        $assignments=$this->assignmentRows($request,$b);
        $appraisals=$this->rows('hr_performance_appraisals',$b,25);
        $competencies=$this->rows('hr_competency_library',$b,25);
        $pips=$this->rows('hr_performance_improvement_plans',$b,25);
        $rewards=$this->rows('hr_performance_rewards',$b,25);
        $stats=[
            'employees'=>$this->count('hr_employees',$b),
            'categories'=>$this->count('hr_kpi_categories',$b),
            'kpis'=>$this->count('hr_kpi_library',$b),
            'cycles'=>$this->count('hr_performance_cycles_kpi',$b),
            'assignments'=>$this->count('hr_employee_kpi_assignments',$b),
            'appraisals'=>$this->count('hr_performance_appraisals',$b),
            'pips'=>$this->count('hr_performance_improvement_plans',$b),
            'rewards'=>$this->count('hr_performance_rewards',$b),
        ];
        return view('hrmanager::performance_kpi.index', compact('employees','categories','kpis','cycles','assignments','appraisals','competencies','pips','rewards','stats'));
    }

    public function assignKpis(Request $request)
    {
        $request->validate(['employee_id'=>'required|integer','performance_cycle_id'=>'required|integer']);
        $this->service->assignKpis([
            'business_id'=>session('business.id'),
            'employee_id'=>$request->employee_id,
            'performance_cycle_id'=>$request->performance_cycle_id,
            'user_id'=>auth()->id(),
        ]);
        return back();
    }

    public function storeAppraisal(Request $request)
    {
        $request->validate(['employee_id'=>'required|integer','performance_cycle_id'=>'required|integer']);
        $this->service->createAppraisal([
            'business_id'=>session('business.id'),
            'employee_id'=>$request->employee_id,
            'performance_cycle_id'=>$request->performance_cycle_id,
            'appraisal_type'=>$request->appraisal_type ?? 'annual',
            'user_id'=>auth()->id(),
        ]);
        return back();
    }

    private function hasTable($t){ try{return DB::getSchemaBuilder()->hasTable($t);}catch(\Throwable $e){return false;} }
    private function rows($t,$b,$l){ return $this->hasTable($t)?DB::table($t)->where('business_id',$b)->orderByDesc('id')->limit($l)->get():collect(); }
    private function count($t,$b){ return $this->hasTable($t)?DB::table($t)->where('business_id',$b)->count():0; }
    private function assignmentRows(Request $r,$b){ if(!$this->hasTable('hr_employee_kpi_assignments')) return collect(); $q=DB::table('hr_employee_kpi_assignments')->where('business_id',$b); if($r->search){$q->where('assignment_no','like','%'.$r->search.'%')->orWhere('assignment_status','like','%'.$r->search.'%');} return $q->orderByDesc('id')->paginate($r->per_page==='all'?1000:(int)$r->get('per_page',25)); }
}
