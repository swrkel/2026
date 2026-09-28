<?php
namespace Modules\PumperDashboardNew\Http\Controllers\Admin;
use Modules\PumperDashboardNew\Entities\PonePdOperator;
use Modules\PumperDashboardNew\Http\Controllers\Controller;
use Modules\PumperDashboardNew\Services\PoneOperatorLedgerService;
class LedgerController extends Controller
{
    public function __construct(private PoneOperatorLedgerService $ledger){}
    public function index(){ $businessId=$this->businessId();$operators=PonePdOperator::query()->where('business_id',$businessId)->orderBy('display_name')->get();$profile=null;$entries=collect();$balance=0.0;if($id=(int)request('operator_profile_id')){$profile=PonePdOperator::query()->whereKey($id)->where('business_id',$businessId)->firstOrFail();$this->ledger->synchronizeOperator($profile);$entries=$this->ledger->entries($profile,request('from'),request('to'));$balance=$entries->last()?->running_balance??0;}return view('pumperdashboardnew::admin.ledger.index',compact('operators','profile','entries','balance'));}
}
