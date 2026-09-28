<?php
namespace Modules\HRManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Services\HrPerformanceService;

class HrPerformanceController extends Controller
{
    protected HrPerformanceService $service;
    public function __construct(HrPerformanceService $service){ $this->service=$service; }

    public function index(Request $request)
    {
        $b=session('business.id');
        $employees=$this->rows('hr_employees',$b,200);
        $cycles=$this->rows('hr_performance_cycles',$b,100);
        $kpis=$this->rows('hr_performance_kpis',$b,25);
        $competencies=$this->rows('hr_performance_competencies',$b,25);
        $goals=$this->goalRows($request,$b);
        $reviews=$this->rows('hr_performance_reviews',$b,25);
        $pips=$this->rows('hr_performance_pips',$b,25);
        $promotions=$this->rows('hr_performance_promotions',$b,25);
        $stats=['employees'=>$this->count('hr_employees',$b),'cycles'=>$this->count('hr_performance_cycles',$b),'kpis'=>$this->count('hr_performance_kpis',$b),'competencies'=>$this->count('hr_performance_competencies',$b),'goals'=>$this->count('hr_performance_goals',$b),'reviews'=>$this->count('hr_performance_reviews',$b),'pips'=>$this->count('hr_performance_pips',$b),'promotions'=>$this->count('hr_performance_promotions',$b)];
        return view('hrmanager::performance.index', compact('employees','cycles','kpis','competencies','goals','reviews','pips','promotions','stats'));
    }

    public function storeGoal(Request $request)
    {
        $request->validate(['goal_title'=>'required|string|max:180']);
        $this->service->createGoal(['business_id'=>session('business.id'),'cycle_id'=>$request->cycle_id,'employee_id'=>$request->employee_id,'goal_scope'=>$request->goal_scope ?? 'employee','goal_title'=>$request->goal_title,'goal_description'=>$request->goal_description,'start_date'=>$request->start_date,'due_date'=>$request->due_date,'target_value'=>$request->target_value ?? 0,'weightage'=>$request->weightage ?? 0,'user_id'=>auth()->id()]);
        return back();
    }

    public function storeReview(Request $request)
    {
        $request->validate(['cycle_id'=>'required|integer','employee_id'=>'required|integer']);
        $this->service->createReview(['business_id'=>session('business.id'),'cycle_id'=>$request->cycle_id,'employee_id'=>$request->employee_id,'reviewer_employee_id'=>$request->reviewer_employee_id,'review_type'=>$request->review_type ?? 'manager','user_id'=>auth()->id()]);
        return back();
    }

    private function hasTable($t){ try{return DB::getSchemaBuilder()->hasTable($t);}catch(\Throwable $e){return false;} }
    private function rows($t,$b,$l){ return $this->hasTable($t)?DB::table($t)->where('business_id',$b)->orderByDesc('id')->limit($l)->get():collect(); }
    private function count($t,$b){ return $this->hasTable($t)?DB::table($t)->where('business_id',$b)->count():0; }
    private function goalRows(Request $r,$b){ if(!$this->hasTable('hr_performance_goals')) return collect(); $q=DB::table('hr_performance_goals')->where('business_id',$b); if($r->search){$q->where('goal_no','like','%'.$r->search.'%')->orWhere('goal_title','like','%'.$r->search.'%')->orWhere('goal_status','like','%'.$r->search.'%');} return $q->orderByDesc('id')->paginate($r->per_page==='all'?1000:(int)$r->get('per_page',25)); }
}
