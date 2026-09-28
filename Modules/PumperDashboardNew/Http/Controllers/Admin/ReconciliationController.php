<?php
namespace Modules\PumperDashboardNew\Http\Controllers\Admin;
use Modules\PumperDashboardNew\Entities\PoneExcessCommission;
use Modules\PumperDashboardNew\Entities\PoneShift;
use Modules\PumperDashboardNew\Entities\PoneShortageRecovery;
use Modules\PumperDashboardNew\Http\Controllers\Controller;
use Modules\PumperDashboardNew\Http\Requests\ExcessCommissionRequest;
use Modules\PumperDashboardNew\Http\Requests\ShortageRecoveryRequest;
use Modules\PumperDashboardNew\Http\Requests\VoidRequest;
use Modules\PumperDashboardNew\Services\PoneReconciliationService;
class ReconciliationController extends Controller
{
    public function __construct(private PoneReconciliationService $reconciliation){}
    public function index(){ $businessId=$this->businessId();$shifts=PoneShift::query()->where('business_id',$businessId)->with(['operatorProfile','shortageRecoveries','excessCommissions'])->latest('opened_at')->limit(500)->get();$recoveries=PoneShortageRecovery::query()->where('business_id',$businessId)->with(['shift','operatorProfile'])->latest('recovery_date')->limit(500)->get();$commissions=PoneExcessCommission::query()->where('business_id',$businessId)->with(['shift','operatorProfile'])->latest('commission_date')->limit(500)->get();return view('pumperdashboardnew::admin.reconciliation.index',compact('shifts','recoveries','commissions'));}
    public function recover(ShortageRecoveryRequest $request,int $shift){$shift=$this->shift($shift);$this->reconciliation->recoverForShift($shift,$request->validated(),(int)auth()->id());return $this->ok(__('pumperdashboardnew::lang.shortage_recovered'));}
    public function commission(ExcessCommissionRequest $request,int $shift){$shift=$this->shift($shift);$this->reconciliation->commissionForShift($shift,$request->validated(),(int)auth()->id());return $this->ok(__('pumperdashboardnew::lang.excess_commission_saved'));}
    public function voidRecovery(VoidRequest $request,int $recovery){$this->reconciliation->voidRecoveryForBusiness($recovery,$request->validated('reason'),$this->businessId(),(int)auth()->id());return $this->ok(__('pumperdashboardnew::lang.recovery_voided'));}
    public function voidCommission(VoidRequest $request,int $commission){$this->reconciliation->voidCommissionForBusiness($commission,$request->validated('reason'),$this->businessId(),(int)auth()->id());return $this->ok(__('pumperdashboardnew::lang.commission_voided'));}
    private function shift(int $id):PoneShift{return PoneShift::query()->whereKey($id)->where('business_id',$this->businessId())->firstOrFail();}
}
