<?php
namespace Modules\HRManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Services\HrMssService;

class HrMssController extends Controller
{
    protected HrMssService $service;
    public function __construct(HrMssService $service){ $this->service=$service; }

    public function index(Request $request)
    {
        $b=session('business.id');
        $employees=$this->rows('hr_employees',$b,300);
        $profiles=$this->rows('hr_mss_profiles',$b,25);
        $teamMembers=$this->teamRows($request,$b);
        $approvals=$this->rows('hr_mss_approval_queue',$b,25);
        $delegations=$this->rows('hr_mss_delegate_approvals',$b,25);
        $notes=$this->rows('hr_mss_team_notes',$b,25);
        $snapshots=$this->rows('hr_mss_team_kpi_snapshots',$b,25);
        $notifications=$this->rows('hr_mss_notifications',$b,25);

        $stats=[
            'employees'=>$this->count('hr_employees',$b),
            'managers'=>$this->count('hr_mss_profiles',$b),
            'team_members'=>$this->count('hr_mss_team_members',$b),
            'approvals'=>$this->count('hr_mss_approval_queue',$b),
            'pending_approvals'=>$this->count('hr_mss_approval_queue',$b,['approval_status'=>'pending']),
            'delegations'=>$this->count('hr_mss_delegate_approvals',$b),
            'notes'=>$this->count('hr_mss_team_notes',$b),
            'notifications'=>$this->count('hr_mss_notifications',$b),
        ];

        return view('hrmanager::mss.index', compact('employees','profiles','teamMembers','approvals','delegations','notes','snapshots','notifications','stats'));
    }

    public function storeTeamMember(Request $request)
    {
        $request->validate(['manager_employee_id'=>'required|integer','employee_id'=>'required|integer']);
        $this->service->addTeamMember([
            'business_id'=>session('business.id'),
            'manager_employee_id'=>$request->manager_employee_id,
            'employee_id'=>$request->employee_id,
            'reporting_type'=>$request->reporting_type ?? 'direct',
            'effective_from'=>$request->effective_from,
            'user_id'=>auth()->id(),
        ]);
        return back();
    }

    public function storeApproval(Request $request)
    {
        $request->validate([
            'manager_employee_id'=>'required|integer',
            'reference_type'=>'required|string|max:100',
            'reference_id'=>'required|integer',
            'approval_title'=>'required|string|max:180',
        ]);

        $this->service->createApproval([
            'business_id'=>session('business.id'),
            'manager_employee_id'=>$request->manager_employee_id,
            'employee_id'=>$request->employee_id,
            'reference_type'=>$request->reference_type,
            'reference_id'=>$request->reference_id,
            'approval_title'=>$request->approval_title,
            'approval_amount'=>$request->approval_amount ?? 0,
            'priority'=>$request->priority ?? 'normal',
            'user_id'=>auth()->id(),
        ]);
        return back();
    }

    private function hasTable($t){ try{return DB::getSchemaBuilder()->hasTable($t);}catch(\Throwable $e){return false;} }
    private function rows($t,$b,$l){ return $this->hasTable($t)?DB::table($t)->where('business_id',$b)->orderByDesc('id')->limit($l)->get():collect(); }
    private function count($t,$b,$w=[]){ if(!$this->hasTable($t)) return 0; $q=DB::table($t)->where('business_id',$b); foreach($w as $k=>$v){$q->where($k,$v);} return $q->count(); }
    private function teamRows(Request $r,$b){ if(!$this->hasTable('hr_mss_team_members')) return collect(); $q=DB::table('hr_mss_team_members')->where('business_id',$b); if($r->search){$q->where('reporting_type','like','%'.$r->search.'%');} return $q->orderByDesc('id')->paginate($r->per_page==='all'?1000:(int)$r->get('per_page',25)); }
}
