<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\EzyLaw\Contracts\FinanceGateway;
use Modules\EzyLaw\Entities\{LawTrustAccount,LawTrustTransaction,LawClient,LawMatter,LawInvoice};
use Modules\EzyLaw\Services\TrustService;
class TrustController extends Controller
{
    public function index(Request $r,TrustService $s){
        $accounts=LawTrustAccount::orderBy('name')->get();$account=$r->filled('account_id')?LawTrustAccount::find((int)$r->input('account_id')):$accounts->first();
        $tx=LawTrustTransaction::with(['client','matter','invoice','account']);if($account)$tx->where('trust_account_id',$account->id);
        return view('ezylaw::trust.index',['accounts'=>$accounts,'account'=>$account,'transactions'=>$tx->orderByDesc('id')->paginate(30),'clients'=>LawClient::where('status','active')->orderBy('name')->get(),'matters'=>LawMatter::whereIn('status',['open','pending'])->orderByDesc('id')->get(),'invoices'=>LawInvoice::where('balance','>',0)->orderByDesc('invoice_date')->get()]);
    }
    public function storeAccount(Request $r,TrustService $s){$d=$r->validate(['location_id'=>'nullable|integer','name'=>'required|string|max:191','account_no'=>'nullable|string|max:100','bank_name'=>'nullable|string|max:191','currency'=>'nullable|string|max:10']);$s->createAccount($d);return back()->with('success','Trust account created.');}
    public function deposit(Request $r,TrustService $s){$d=$r->validate(['trust_account_id'=>'required|integer','client_id'=>'required|integer','matter_id'=>'nullable|integer','transaction_date'=>'required|date','amount'=>'required|numeric|min:0.0001','reference'=>'nullable|string|max:100','description'=>'nullable|string']);$account=LawTrustAccount::findOrFail($d['trust_account_id']);$s->post($account,$d+['type'=>'deposit','direction'=>'credit']);return back()->with('success','Trust deposit recorded.');}
    public function withdrawal(Request $r,TrustService $s){$d=$r->validate(['trust_account_id'=>'required|integer','client_id'=>'required|integer','matter_id'=>'nullable|integer','transaction_date'=>'required|date','type'=>'required|in:refund,disbursement','amount'=>'required|numeric|min:0.0001','reference'=>'nullable|string|max:100','payee'=>'nullable|string|max:191','description'=>'nullable|string']);$account=LawTrustAccount::findOrFail($d['trust_account_id']);$s->post($account,$d+['direction'=>'debit']);return back()->with('success','Trust withdrawal recorded.');}
    public function applyInvoice(Request $r,TrustService $s){$d=$r->validate(['trust_account_id'=>'required|integer','invoice_id'=>'required|integer','transaction_date'=>'required|date','amount'=>'required|numeric|min:0.0001','reference'=>'nullable|string|max:100','description'=>'nullable|string']);$s->applyToInvoice(LawTrustAccount::findOrFail($d['trust_account_id']),LawInvoice::findOrFail($d['invoice_id']),$d);return back()->with('success','Trust funds applied to invoice.');}
    public function sync(LawTrustTransaction $transaction,FinanceGateway $finance){$result=$finance->syncTrustTransaction($transaction->id);$transaction->update(['finance_sync_status'=>$result['status']]);return back()->with($result['success']?'success':'warning',$result['message']);}
}
