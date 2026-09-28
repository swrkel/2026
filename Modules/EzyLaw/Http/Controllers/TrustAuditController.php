<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Routing\Controller;
use Modules\EzyLaw\Entities\{LawTrustAccount,LawTrustTransaction,LawTrustReconciliation};
class TrustAuditController extends Controller {
 public function index(Request $r){$from=$r->input('from',now()->startOfMonth()->toDateString());$to=$r->input('to',now()->endOfMonth()->toDateString());$accounts=LawTrustAccount::orderBy('name')->get();$audit=[];foreach($accounts as $a){$in=(float)LawTrustTransaction::where('trust_account_id',$a->id)->where('direction','credit')->sum('amount');$out=(float)LawTrustTransaction::where('trust_account_id',$a->id)->where('direction','debit')->sum('amount');$audit[]=['account'=>$a,'ledger_balance'=>$in-$out,'stored_balance'=>(float)$a->current_balance,'difference'=>(float)$a->current_balance-($in-$out)];}return view('ezylaw::trust.audit',['from'=>$from,'to'=>$to,'audit'=>$audit,'transactions'=>LawTrustTransaction::with(['client','matter','account'])->whereBetween('transaction_date',[$from,$to])->orderByDesc('transaction_date')->get(),'reconciliations'=>LawTrustReconciliation::with('account')->whereBetween('statement_date',[$from,$to])->orderByDesc('statement_date')->get()]);}
}
