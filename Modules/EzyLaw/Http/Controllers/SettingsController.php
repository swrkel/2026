<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Routing\Controller;
use Modules\EzyLaw\Entities\{LawPracticeArea,LawCourt}; use Modules\EzyLaw\Services\SettingsService; use Modules\EzyLaw\Utilities\EzyLawTenantGuard;
class SettingsController extends Controller
{
 public function index(SettingsService $s){
    $keys=['client_prefix','client_next','matter_prefix','matter_next','invoice_prefix','invoice_next','retainer_prefix','retainer_next','conflict_prefix','conflict_next','estimate_prefix','estimate_next','advance_prefix','advance_next','settlement_prefix','settlement_next','default_hourly_rate','finance_receivable_account_id','finance_fee_income_account_id','finance_cash_account_id','finance_expense_account_id','finance_trust_cash_account_id','finance_client_trust_liability_account_id','finance_client_advance_liability_account_id','finance_expense_advance_account_id'];
    $settings=[];foreach($keys as $k)$settings[$k]=$s->get($k);
    return view('ezylaw::settings.index',['settings'=>$settings,'areas'=>LawPracticeArea::orderBy('name')->get(),'courts'=>LawCourt::orderBy('name')->get()]);
 }
 public function update(Request $r,SettingsService $s){foreach($r->except(['_token','_method']) as $k=>$v){if(str_starts_with($k,'setting_'))$s->put(substr($k,8),$v);}return back()->with('success','EzyLaw settings saved.');}
 public function area(Request $r){$d=$r->validate(['name'=>'required|string|max:191','code'=>'nullable|string|max:50','description'=>'nullable|string']);LawPracticeArea::create($d+['business_id'=>EzyLawTenantGuard::businessId(),'active'=>1]);return back()->with('success','Practice area added.');}
 public function court(Request $r){$d=$r->validate(['name'=>'required|string|max:191','code'=>'nullable|string|max:50','court_type'=>'nullable|string|max:100','address'=>'nullable|string|max:255','city'=>'nullable|string|max:100','phone'=>'nullable|string|max:50','email'=>'nullable|email|max:191']);LawCourt::create($d+['business_id'=>EzyLawTenantGuard::businessId(),'active'=>1]);return back()->with('success','Court added.');}
}
