<?php
namespace Modules\HRManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Services\HrCompetencyService;

class HrCompetencyController extends Controller
{
    protected HrCompetencyService $service;
    public function __construct(HrCompetencyService $service){ $this->service=$service; }

    public function index(Request $request)
    {
        $b=session('business.id');
        $employees=$this->rows('hr_employees',$b,300);
        $groups=$this->rows('hr_competency_groups',$b,100);
        $competencies=$this->competencyRows($request,$b);
        $levels=$this->rows('hr_competency_levels',$b,50);
        $requirements=$this->rows('hr_designation_competency_requirements',$b,50);
        $assessments=$this->rows('hr_employee_competency_assessments',$b,25);
        $matrix=$this->rows('hr_employee_skill_matrix',$b,25);
        $actions=$this->rows('hr_competency_gap_actions',$b,25);

        $stats=[
            'employees'=>$this->count('hr_employees',$b),
            'groups'=>$this->count('hr_competency_groups',$b),
            'competencies'=>$this->count('hr_competencies',$b),
            'levels'=>$this->count('hr_competency_levels',$b),
            'requirements'=>$this->count('hr_designation_competency_requirements',$b),
            'assessments'=>$this->count('hr_employee_competency_assessments',$b),
            'matrix'=>$this->count('hr_employee_skill_matrix',$b),
            'gap_actions'=>$this->count('hr_competency_gap_actions',$b),
        ];

        return view('hrmanager::competencies.index', compact('employees','groups','competencies','levels','requirements','assessments','matrix','actions','stats'));
    }

    public function storeCompetency(Request $request)
    {
        $request->validate(['competency_name'=>'required|string|max:180']);
        $this->service->createCompetency([
            'business_id'=>session('business.id'),
            'competency_name'=>$request->competency_name,
            'competency_group_id'=>$request->competency_group_id,
            'competency_type'=>$request->competency_type ?? 'technical',
            'description'=>$request->description,
            'user_id'=>auth()->id(),
        ]);
        return back();
    }

    public function storeAssessment(Request $request)
    {
        $request->validate(['employee_id'=>'required|integer']);
        $this->service->createAssessment([
            'business_id'=>session('business.id'),
            'employee_id'=>$request->employee_id,
            'assessment_date'=>$request->assessment_date,
            'assessment_type'=>$request->assessment_type ?? 'annual',
            'user_id'=>auth()->id(),
        ]);
        return back();
    }

    private function hasTable($t){ try{return DB::getSchemaBuilder()->hasTable($t);}catch(\Throwable $e){return false;} }
    private function rows($t,$b,$l){ return $this->hasTable($t)?DB::table($t)->where('business_id',$b)->orderByDesc('id')->limit($l)->get():collect(); }
    private function count($t,$b,$w=[]){ if(!$this->hasTable($t)) return 0; $q=DB::table($t)->where('business_id',$b); foreach($w as $k=>$v){$q->where($k,$v);} return $q->count(); }
    private function competencyRows(Request $r,$b){ if(!$this->hasTable('hr_competencies')) return collect(); $q=DB::table('hr_competencies')->where('business_id',$b); if($r->search){$q->where('competency_code','like','%'.$r->search.'%')->orWhere('competency_name','like','%'.$r->search.'%')->orWhere('competency_type','like','%'.$r->search.'%');} return $q->orderByDesc('id')->paginate($r->per_page==='all'?1000:(int)$r->get('per_page',25)); }
}
