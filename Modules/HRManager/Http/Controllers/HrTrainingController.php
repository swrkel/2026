<?php
namespace Modules\HRManager\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Services\HrTrainingService;

class HrTrainingController extends Controller
{
    protected HrTrainingService $service;
    public function __construct(HrTrainingService $service){ $this->service=$service; }

    public function index(Request $request)
    {
        $b=session('business.id');
        $employees=$this->rows('hr_employees',$b,200);
        $categories=$this->rows('hr_training_categories',$b,25);
        $courses=$this->rows('hr_training_courses',$b,100);
        $sessions=$this->sessionRows($request,$b);
        $enrollments=$this->rows('hr_training_enrollments',$b,25);
        $certificates=$this->rows('hr_training_certificates',$b,25);
        $skillGaps=$this->rows('hr_training_skill_gaps',$b,25);
        $stats=['employees'=>$this->count('hr_employees',$b),'categories'=>$this->count('hr_training_categories',$b),'courses'=>$this->count('hr_training_courses',$b),'sessions'=>$this->count('hr_training_sessions',$b),'enrollments'=>$this->count('hr_training_enrollments',$b),'certificates'=>$this->count('hr_training_certificates',$b),'skill_gaps'=>$this->count('hr_training_skill_gaps',$b),'assessments'=>$this->count('hr_training_assessments',$b)];
        return view('hrmanager::training.index', compact('employees','categories','courses','sessions','enrollments','certificates','skillGaps','stats'));
    }

    public function enroll(Request $request)
    {
        $request->validate(['training_session_id'=>'required|integer','training_course_id'=>'required|integer','employee_id'=>'required|integer']);
        $this->service->enroll(['business_id'=>session('business.id'),'training_session_id'=>$request->training_session_id,'training_course_id'=>$request->training_course_id,'employee_id'=>$request->employee_id,'user_id'=>auth()->id()]);
        return back();
    }

    private function hasTable($t){ try{return DB::getSchemaBuilder()->hasTable($t);}catch(\Throwable $e){return false;} }
    private function rows($t,$b,$l){ return $this->hasTable($t)?DB::table($t)->where('business_id',$b)->orderByDesc('id')->limit($l)->get():collect(); }
    private function count($t,$b){ return $this->hasTable($t)?DB::table($t)->where('business_id',$b)->count():0; }
    private function sessionRows(Request $r,$b){ if(!$this->hasTable('hr_training_sessions')) return collect(); $q=DB::table('hr_training_sessions')->where('business_id',$b); if($r->search){$q->where('session_no','like','%'.$r->search.'%')->orWhere('session_title','like','%'.$r->search.'%')->orWhere('session_status','like','%'.$r->search.'%');} return $q->orderByDesc('id')->paginate($r->per_page==='all'?1000:(int)$r->get('per_page',25)); }
}
