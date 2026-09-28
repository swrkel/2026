<?php
namespace Modules\HRManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Services\HrEssService;

class HrEssController extends Controller
{
    protected HrEssService $service;
    public function __construct(HrEssService $service){ $this->service=$service; }

    public function index(Request $request)
    {
        $b=session('business.id');
        $employees=$this->rows('hr_employees',$b,200);
        $profiles=$this->rows('hr_ess_profiles',$b,25);
        $requests=$this->requestRows($request,$b);
        $notifications=$this->rows('hr_ess_notifications',$b,25);
        $documents=$this->rows('hr_ess_documents',$b,25);
        $changeRequests=$this->rows('hr_ess_profile_change_requests',$b,25);
        $tickets=$this->rows('hr_ess_helpdesk_tickets',$b,25);
        $stats=[
            'employees'=>$this->count('hr_employees',$b),
            'profiles'=>$this->count('hr_ess_profiles',$b),
            'requests'=>$this->count('hr_ess_requests',$b),
            'pending'=>$this->count('hr_ess_requests',$b,['request_status'=>'pending']),
            'notifications'=>$this->count('hr_ess_notifications',$b),
            'documents'=>$this->count('hr_ess_documents',$b),
            'changes'=>$this->count('hr_ess_profile_change_requests',$b),
            'tickets'=>$this->count('hr_ess_helpdesk_tickets',$b),
        ];
        return view('hrmanager::ess.index', compact('employees','profiles','requests','notifications','documents','changeRequests','tickets','stats'));
    }

    public function storeRequest(Request $request)
    {
        $request->validate(['employee_id'=>'required|integer','request_type'=>'required|string','request_title'=>'required|string|max:180']);
        $this->service->createRequest([
            'business_id'=>session('business.id'),
            'employee_id'=>$request->employee_id,
            'request_type'=>$request->request_type,
            'request_title'=>$request->request_title,
            'request_description'=>$request->request_description,
            'user_id'=>auth()->id(),
        ]);
        return back();
    }

    public function storeTicket(Request $request)
    {
        $request->validate(['employee_id'=>'required|integer','subject'=>'required|string|max:180']);
        $this->service->createTicket([
            'business_id'=>session('business.id'),
            'employee_id'=>$request->employee_id,
            'category'=>$request->category,
            'subject'=>$request->subject,
            'description'=>$request->description,
            'priority'=>$request->priority,
            'user_id'=>auth()->id(),
        ]);
        return back();
    }

    private function hasTable($t){ try{return DB::getSchemaBuilder()->hasTable($t);}catch(\Throwable $e){return false;} }
    private function rows($t,$b,$l){ return $this->hasTable($t)?DB::table($t)->where('business_id',$b)->orderByDesc('id')->limit($l)->get():collect(); }
    private function count($t,$b,$w=[]){ if(!$this->hasTable($t)) return 0; $q=DB::table($t)->where('business_id',$b); foreach($w as $k=>$v){$q->where($k,$v);} return $q->count(); }
    private function requestRows(Request $r,$b){ if(!$this->hasTable('hr_ess_requests')) return collect(); $q=DB::table('hr_ess_requests')->where('business_id',$b); if($r->search){$q->where('request_no','like','%'.$r->search.'%')->orWhere('request_title','like','%'.$r->search.'%')->orWhere('request_status','like','%'.$r->search.'%');} return $q->orderByDesc('id')->paginate($r->per_page==='all'?1000:(int)$r->get('per_page',25)); }
}
