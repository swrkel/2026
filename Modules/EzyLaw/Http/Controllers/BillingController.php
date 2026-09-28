<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Routing\Controller; use Modules\EzyLaw\Entities\{LawInvoice,LawClient,LawMatter}; use Modules\EzyLaw\Services\BillingService; use Modules\EzyLaw\Contracts\FinanceGateway;
class BillingController extends Controller
{
 public function index(){return view('ezylaw::billing.index',['invoices'=>LawInvoice::with(['client','matter'])->orderByDesc('invoice_date')->paginate(25),'clients'=>LawClient::where('status','active')->orderBy('name')->get(),'matters'=>LawMatter::whereIn('status',['open','pending'])->orderBy('matter_no')->get()]);}
 public function store(Request $r,BillingService $s){$d=$r->validate(['client_id'=>'required|integer','matter_id'=>'nullable|integer','invoice_date'=>'required|date','due_date'=>'nullable|date','description'=>'required|string|max:1000','amount'=>'required|numeric|min:0','tax_amount'=>'nullable|numeric|min:0','discount_amount'=>'nullable|numeric|min:0']);$invoice=$s->createInvoice(['client_id'=>$d['client_id'],'matter_id'=>$d['matter_id']??null,'invoice_date'=>$d['invoice_date'],'due_date'=>$d['due_date']??null,'tax_amount'=>$d['tax_amount']??0,'discount_amount'=>$d['discount_amount']??0],[['description'=>$d['description'],'qty'=>1,'unit_price'=>$d['amount']]]);return back()->with('success','Invoice '.$invoice->invoice_no.' created.');}
 public function show(LawInvoice $invoice){$invoice->load(['client','matter','lines','payments','adjustments']);return view('ezylaw::billing.show',compact('invoice'));}
 public function payment(Request $r,LawInvoice $invoice,BillingService $s){$d=$r->validate(['payment_date'=>'required|date','amount'=>'required|numeric|min:0.0001','method'=>'required|string|max:50','reference'=>'nullable|string|max:100','notes'=>'nullable|string']);$p=$s->recordPayment($invoice,$d);return back()->with('success','Payment recorded.');}
 public function sync(LawInvoice $invoice,FinanceGateway $finance){$result=$finance->syncInvoice($invoice->id);$invoice->update(['finance_sync_status'=>$result['status']]);return back()->with($result['success']?'success':'warning',$result['message']);}
}
