<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Routing\Controller;
use Modules\EzyLaw\Entities\{LawTrustAccount,LawTrustReconciliation}; use Modules\EzyLaw\Services\TrustReconciliationService;
class TrustReconciliationController extends Controller {
 public function index(Request $r){$recId=$r->input('reconciliation_id');return view('ezylaw::trust.reconcile',['accounts'=>LawTrustAccount::where('active',1)->orderBy('name')->get(),'reconciliations'=>LawTrustReconciliation::with('account')->orderByDesc('statement_date')->paginate(20),'selected'=>$recId?LawTrustReconciliation::with(['account','lines.transaction'])->find($recId):null]);}
 public function store(Request $r,TrustReconciliationService $s){$d=$r->validate(['trust_account_id'=>'required|integer','statement_date'=>'required|date','statement_balance'=>'required|numeric','notes'=>'nullable|string']);$rec=$s->start(LawTrustAccount::findOrFail($d['trust_account_id']),$d);return redirect()->route('ezylaw.trust.reconcile',['reconciliation_id'=>$rec->id])->with('success','Trust reconciliation opened.');}
 public function complete(Request $r,LawTrustReconciliation $reconciliation,TrustReconciliationService $s){$s->complete($reconciliation,array_map('intval',$r->input('matched',[])));return back()->with('success','Trust reconciliation completed.');}
}
