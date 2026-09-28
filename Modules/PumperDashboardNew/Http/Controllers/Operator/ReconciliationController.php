<?php
namespace Modules\PumperDashboardNew\Http\Controllers\Operator;
use Modules\PumperDashboardNew\Entities\PoneExcessCommission;
use Modules\PumperDashboardNew\Entities\PoneShortageRecovery;
use Modules\PumperDashboardNew\Http\Controllers\Controller;
use Modules\PumperDashboardNew\Http\Requests\ExcessCommissionRequest;
use Modules\PumperDashboardNew\Http\Requests\ShortageRecoveryRequest;
use Modules\PumperDashboardNew\Http\Requests\VoidRequest;
use Modules\PumperDashboardNew\Services\PoneContextService;
use Modules\PumperDashboardNew\Services\PoneReconciliationService;
use Modules\PumperDashboardNew\Services\PoneShiftTotalsService;
class ReconciliationController extends Controller
{
    public function __construct(private PoneContextService $context,private PoneReconciliationService $reconciliation,private PoneShiftTotalsService $totals){}
    public function index(){ $shift=$this->totals->refresh($this->context->shift());$recoveries=PoneShortageRecovery::query()->where('shift_id',$shift->id)->latest('recovery_date')->get();$commissions=PoneExcessCommission::query()->where('shift_id',$shift->id)->latest('commission_date')->get();$availableShortage=$this->reconciliation->availableShortage($shift->id);$availableExcess=$this->reconciliation->availableExcess($shift->id);return view('pumperdashboardnew::operator.reconciliation.index',compact('shift','recoveries','commissions','availableShortage','availableExcess'));}
    public function recover(ShortageRecoveryRequest $request){$this->reconciliation->recoverShortage($request->validated());return $this->ok(__('pumperdashboardnew::lang.shortage_recovered'));}
    public function commission(ExcessCommissionRequest $request){$this->reconciliation->createExcessCommission($request->validated());return $this->ok(__('pumperdashboardnew::lang.excess_commission_saved'));}
    public function voidRecovery(VoidRequest $request,int $recovery){$this->reconciliation->voidRecovery($recovery,$request->validated('reason'));return $this->ok(__('pumperdashboardnew::lang.recovery_voided'));}
    public function voidCommission(VoidRequest $request,int $commission){$this->reconciliation->voidCommission($commission,$request->validated('reason'));return $this->ok(__('pumperdashboardnew::lang.commission_voided'));}
}
