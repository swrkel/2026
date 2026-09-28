<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Routing\Controller;
use Modules\EzyLaw\Entities\{LawBillingRate,LawMatter,LawPracticeArea,LawInvoice,LawInvoiceAdjustment};
use Modules\EzyLaw\Services\AdvancedBillingService; use Modules\EzyLaw\Utilities\{EzyLawTenantGuard,EzyLawReferenceGuard}; use Modules\EzyLaw\Contracts\FinanceGateway;
class AdvancedBillingController extends Controller {
 public function rates(){return view('ezylaw::billing.rates',['rates'=>LawBillingRate::with(['matter','practiceArea'])->orderByDesc('id')->paginate(30),'matters'=>LawMatter::orderBy('matter_no')->get(),'areas'=>LawPracticeArea::where('active',1)->orderBy('name')->get()]);}
 public function storeRate(Request $r){$d=$r->validate(['user_id'=>'nullable|integer','practice_area_id'=>'nullable|integer','matter_id'=>'nullable|integer','rate_type'=>'required|string|max:30','hourly_rate'=>'nullable|numeric|min:0','fixed_rate'=>'nullable|numeric|min:0','effective_from'=>'nullable|date','effective_to'=>'nullable|date']);EzyLawReferenceGuard::matter($d['matter_id']??null);EzyLawReferenceGuard::practiceArea($d['practice_area_id']??null);LawBillingRate::create($d+['business_id'=>EzyLawTenantGuard::businessId(),'active'=>1]);return back()->with('success','Billing rate saved.');}
 public function destroyRate(LawBillingRate $rate){$rate->delete();return back()->with('success','Billing rate removed.');}
 public function preview(LawMatter $matter,AdvancedBillingService $s){return view('ezylaw::billing.preview',['matter'=>$matter->load('client'),'preview'=>$s->preview($matter)]);}
 public function createInvoice(Request $r,LawMatter $matter,AdvancedBillingService $s){$d=$r->validate(['invoice_date'=>'required|date','due_date'=>'nullable|date','tax_amount'=>'nullable|numeric|min:0','discount_amount'=>'nullable|numeric|min:0','notes'=>'nullable|string']);try{$invoice=$s->createMatterInvoice($matter,$d);return redirect()->route('ezylaw.billing.show',$invoice)->with('success','Matter invoice created from unbilled time and expenses.');}catch(\Throwable $e){return back()->with('warning',$e->getMessage());}}
 public function adjustment(Request $r,LawInvoice $invoice,AdvancedBillingService $s){$d=$r->validate(['adjustment_type'=>'required|in:credit,debit','amount'=>'required|numeric|min:0.0001','reason'=>'required|string|max:1000']);$s->adjust($invoice,$d['adjustment_type'],(float)$d['amount'],$d['reason']);return back()->with('success','Invoice adjustment recorded.');}
 public function syncAdjustment(LawInvoiceAdjustment $adjustment,FinanceGateway $finance){$result=$finance->syncInvoiceAdjustment($adjustment->id);return back()->with($result['success']?'success':'warning',$result['message']);}
}
