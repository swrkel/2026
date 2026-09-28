<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Routing\Controller; use Modules\EzyLaw\Entities\{LawHearing,LawMatter,LawCourt}; use Modules\EzyLaw\Utilities\{EzyLawTenantGuard,EzyLawReferenceGuard};
class HearingController extends Controller
{
 public function index(Request $r){$q=LawHearing::with(['matter','court']); if($r->filled('from'))$q->whereDate('hearing_at','>=',$r->from); if($r->filled('to'))$q->whereDate('hearing_at','<=',$r->to); return view('ezylaw::hearings.index',['hearings'=>$q->orderBy('hearing_at')->paginate(25)->appends($r->query()),'matters'=>LawMatter::whereIn('status',['open','pending'])->orderBy('matter_no')->get(),'courts'=>LawCourt::where('active',1)->orderBy('name')->get()]);}
 public function store(Request $r){$d=$r->validate(['matter_id'=>'required|integer','court_id'=>'nullable|integer','hearing_at'=>'required|date','hearing_type'=>'nullable|string|max:100','judge_name'=>'nullable|string|max:191','purpose'=>'nullable|string','outcome'=>'nullable|string','next_hearing_at'=>'nullable|date','status'=>'required|in:scheduled,completed,adjourned,cancelled','reminder_minutes'=>'nullable|integer|min:0']); EzyLawReferenceGuard::matter($d['matter_id']);EzyLawReferenceGuard::court($d['court_id']??null); LawHearing::create($d+['business_id'=>EzyLawTenantGuard::businessId(),'created_by'=>auth()->id()]); return back()->with('success','Hearing saved.');}
 public function destroy(LawHearing $hearing){$hearing->delete(); return back()->with('success','Hearing removed.');}
}
