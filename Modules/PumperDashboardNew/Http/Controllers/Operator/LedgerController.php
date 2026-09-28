<?php
namespace Modules\PumperDashboardNew\Http\Controllers\Operator;
use Modules\PumperDashboardNew\Http\Controllers\Controller;
use Modules\PumperDashboardNew\Services\PoneContextService;
use Modules\PumperDashboardNew\Services\PoneOperatorLedgerService;
use Modules\PumperDashboardNew\Services\PoneSharedMasterDataService;
class LedgerController extends Controller
{
    public function __construct(private PoneContextService $context,private PoneOperatorLedgerService $ledger,private PoneSharedMasterDataService $masterData){}
    public function index(){ $profile=$this->context->profile();$this->ledger->synchronizeOperator($profile);$from=request('from');$to=request('to');$entries=$this->ledger->entries($profile,$from,$to);$balance=$entries->last()?->running_balance??0;$business=$this->masterData->business($profile->business_id);return view('pumperdashboardnew::operator.ledger.index',compact('profile','entries','balance','from','to','business'));}
}
