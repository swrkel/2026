<?php
namespace Modules\HRManager\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Services\HrClaimService;

class HrClaimController extends Controller
{
    protected HrClaimService $service;
    public function __construct(HrClaimService $service){ $this->service=$service; }

    public function index(Request $request)
    {
        $b=session('business.id');
        $employees=$this->rows('hr_employees',$b,200);
        $categories=$this->rows('hr_claim_categories',$b,25);
        $travelRequests=$this->rows('hr_travel_requests',$b,25);
        $claims=$this->claimRows($request,$b);
        $approvals=$this->rows('hr_claim_approvals',$b,25);
        $payments=$this->rows('hr_claim_payments',$b,25);
        $stats=['employees'=>$this->count('hr_employees',$b),'categories'=>$this->count('hr_claim_categories',$b),'travel_requests'=>$this->count('hr_travel_requests',$b),'claims'=>$this->count('hr_expense_claims',$b),'pending'=>$this->count('hr_expense_claims',$b,['approval_status'=>'pending']),'approved'=>$this->count('hr_expense_claims',$b,['approval_status'=>'approved']),'paid'=>$this->count('hr_expense_claims',$b,['payment_status'=>'paid']),'claim_total'=>$this->sum('hr_expense_claims',$b,'total_amount')];
        return view('hrmanager::claims.index', compact('employees','categories','travelRequests','claims','approvals','payments','stats'));
    }

    public function storeClaim(Request $request)
    {
        $request->validate(['employee_id'=>'required|integer','claim_title'=>'required|string|max:180']);
        $this->service->createClaim(['business_id'=>session('business.id'),'employee_id'=>$request->employee_id,'claim_title'=>$request->claim_title,'claim_date'=>$request->claim_date,'total_amount'=>$request->total_amount ?? 0,'remarks'=>$request->remarks,'user_id'=>auth()->id()]);
        return back();
    }

    private function hasTable($t){ try{return DB::getSchemaBuilder()->hasTable($t);}catch(\Throwable $e){return false;} }
    private function rows($t,$b,$l){ return $this->hasTable($t)?DB::table($t)->where('business_id',$b)->orderByDesc('id')->limit($l)->get():collect(); }
    private function count($t,$b,$w=[]){ if(!$this->hasTable($t)) return 0; $q=DB::table($t)->where('business_id',$b); foreach($w as $k=>$v){$q->where($k,$v);} return $q->count(); }
    private function sum($t,$b,$c){ return $this->hasTable($t)?(float)DB::table($t)->where('business_id',$b)->sum($c):0; }
    private function claimRows(Request $r,$b){ if(!$this->hasTable('hr_expense_claims')) return collect(); $q=DB::table('hr_expense_claims')->where('business_id',$b); if($r->search){$q->where('claim_no','like','%'.$r->search.'%')->orWhere('claim_title','like','%'.$r->search.'%')->orWhere('claim_status','like','%'.$r->search.'%');} if($r->date_from){$q->whereDate('claim_date','>=',$r->date_from);} if($r->date_to){$q->whereDate('claim_date','<=',$r->date_to);} return $q->orderByDesc('id')->paginate($r->per_page==='all'?1000:(int)$r->get('per_page',25)); }
}
