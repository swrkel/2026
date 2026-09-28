<?php
namespace Modules\HRManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Services\HrRecruitmentService;

class HrRecruitmentController extends Controller
{
    protected HrRecruitmentService $service;
    public function __construct(HrRecruitmentService $service){ $this->service = $service; }

    public function index(Request $request)
    {
        $b = session('business.id');
        $requisitions = $this->rows('hr_job_requisitions', $b, 25);
        $openings = $this->rows('hr_job_openings', $b, 100);
        $candidates = $this->candidateRows($request, $b);
        $applications = $this->rows('hr_job_applications', $b, 25);
        $interviews = $this->rows('hr_interviews', $b, 25);
        $offers = $this->rows('hr_job_offers', $b, 25);
        $onboarding = $this->rows('hr_onboarding_plans', $b, 25);
        $employees = $this->rows('hr_employees', $b, 200);

        $stats = [
            'requisitions'=>$this->count('hr_job_requisitions',$b),
            'openings'=>$this->count('hr_job_openings',$b),
            'candidates'=>$this->count('hr_candidates',$b),
            'applications'=>$this->count('hr_job_applications',$b),
            'interviews'=>$this->count('hr_interviews',$b),
            'offers'=>$this->count('hr_job_offers',$b),
            'onboarding'=>$this->count('hr_onboarding_plans',$b),
            'employees'=>$this->count('hr_employees',$b),
        ];

        return view('hrmanager::recruitment.index', compact('requisitions','openings','candidates','applications','interviews','offers','onboarding','employees','stats'));
    }

    public function storeCandidate(Request $request)
    {
        $request->validate(['full_name'=>'required|string|max:180']);
        $this->service->createCandidate([
            'business_id'=>session('business.id'),
            'full_name'=>$request->full_name,
            'mobile'=>$request->mobile,
            'email'=>$request->email,
            'source'=>$request->source,
            'user_id'=>auth()->id(),
        ]);
        return back();
    }

    private function hasTable($table){ try{return DB::getSchemaBuilder()->hasTable($table);}catch(\Throwable $e){return false;} }
    private function rows($table,$b,$limit){ return $this->hasTable($table)?DB::table($table)->where('business_id',$b)->orderByDesc('id')->limit($limit)->get():collect(); }
    private function count($table,$b){ return $this->hasTable($table)?DB::table($table)->where('business_id',$b)->count():0; }
    private function candidateRows(Request $r,$b){
        if(!$this->hasTable('hr_candidates')) return collect();
        $q=DB::table('hr_candidates')->where('business_id',$b);
        if($r->search){ $q->where('candidate_no','like','%'.$r->search.'%')->orWhere('full_name','like','%'.$r->search.'%')->orWhere('mobile','like','%'.$r->search.'%'); }
        return $q->orderByDesc('id')->paginate($r->per_page==='all'?1000:(int)$r->get('per_page',25));
    }
}
