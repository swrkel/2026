<?php
namespace Modules\HRManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Services\HrRecruitmentTalentService;

class HrRecruitmentTalentController extends Controller
{
    protected HrRecruitmentTalentService $service;
    public function __construct(HrRecruitmentTalentService $service){ $this->service=$service; }

    public function index(Request $request)
    {
        $b=session('business.id');
        $positions=$this->rows('hr_recruitment_job_positions',$b,25);
        $requisitions=$this->rows('hr_recruitment_requisitions',$b,25);
        $vacancies=$this->rows('hr_recruitment_vacancies',$b,100);
        $candidates=$this->candidateRows($request,$b);
        $applications=$this->rows('hr_recruitment_applications',$b,25);
        $interviews=$this->rows('hr_recruitment_interviews',$b,25);
        $offers=$this->rows('hr_recruitment_offers',$b,25);
        $joining=$this->rows('hr_recruitment_joining_workflows',$b,25);
        $stats=[
            'positions'=>$this->count('hr_recruitment_job_positions',$b),
            'requisitions'=>$this->count('hr_recruitment_requisitions',$b),
            'vacancies'=>$this->count('hr_recruitment_vacancies',$b),
            'candidates'=>$this->count('hr_recruitment_candidates',$b),
            'applications'=>$this->count('hr_recruitment_applications',$b),
            'interviews'=>$this->count('hr_recruitment_interviews',$b),
            'offers'=>$this->count('hr_recruitment_offers',$b),
            'joining'=>$this->count('hr_recruitment_joining_workflows',$b),
        ];
        return view('hrmanager::recruitment.talent', compact('positions','requisitions','vacancies','candidates','applications','interviews','offers','joining','stats'));
    }

    public function storeCandidate(Request $request)
    {
        $request->validate(['full_name'=>'required|string|max:180']);
        $this->service->createCandidate([
            'business_id'=>session('business.id'),
            'full_name'=>$request->full_name,
            'mobile'=>$request->mobile,
            'email'=>$request->email,
            'nic_no'=>$request->nic_no,
            'source'=>$request->source,
            'user_id'=>auth()->id(),
        ]);
        return back();
    }

    public function storeApplication(Request $request)
    {
        $request->validate(['candidate_id'=>'required|integer','vacancy_id'=>'required|integer']);
        $this->service->apply([
            'business_id'=>session('business.id'),
            'candidate_id'=>$request->candidate_id,
            'vacancy_id'=>$request->vacancy_id,
            'user_id'=>auth()->id(),
        ]);
        return back();
    }

    private function hasTable($t){ try{return DB::getSchemaBuilder()->hasTable($t);}catch(\Throwable $e){return false;} }
    private function rows($t,$b,$l){ return $this->hasTable($t)?DB::table($t)->where('business_id',$b)->orderByDesc('id')->limit($l)->get():collect(); }
    private function count($t,$b){ return $this->hasTable($t)?DB::table($t)->where('business_id',$b)->count():0; }
    private function candidateRows(Request $r,$b){ if(!$this->hasTable('hr_recruitment_candidates')) return collect(); $q=DB::table('hr_recruitment_candidates')->where('business_id',$b); if($r->search){$q->where('candidate_no','like','%'.$r->search.'%')->orWhere('full_name','like','%'.$r->search.'%')->orWhere('mobile','like','%'.$r->search.'%');} return $q->orderByDesc('id')->paginate($r->per_page==='all'?1000:(int)$r->get('per_page',25)); }
}
