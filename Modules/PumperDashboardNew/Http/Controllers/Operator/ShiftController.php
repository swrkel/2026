<?php
namespace Modules\PumperDashboardNew\Http\Controllers\Operator;
use Modules\PumperDashboardNew\Http\Controllers\Controller;
use Modules\PumperDashboardNew\Http\Requests\ShiftCloseRequest;
use Modules\PumperDashboardNew\Services\PoneContextService;
use Modules\PumperDashboardNew\Services\PonePrintService;
use Modules\PumperDashboardNew\Services\PoneSharedMasterDataService;
use Modules\PumperDashboardNew\Services\PoneShiftService;
use Modules\PumperDashboardNew\Services\PoneShiftTotalsService;
class ShiftController extends Controller
{
    public function __construct(private PoneContextService $context,private PoneShiftService $shifts,private PoneShiftTotalsService $totals,private PonePrintService $prints,private PoneSharedMasterDataService $masterData){}
    public function summary(){ $shift=$this->totals->refresh($this->context->shift());$shift->load(['assignments.events','payments.creditSale.lines','payments.cashDenominations','payments.cardLines','otherSales.lines','unloadStocks.lines','dayEntries','collections','settlementReferences','shortageRecoveries','excessCommissions']);return view('pumperdashboardnew::operator.shifts.summary',compact('shift'));}
    public function closeForm(){ $shift=$this->totals->refresh($this->context->shift());$openPumps=$shift->assignments()->whereNotIn('status',['closed','cancelled'])->get();$hasCollection=$shift->collections()->where('status','confirmed')->exists();return view('pumperdashboardnew::operator.shifts.close',compact('shift','openPumps','hasCollection'));}
    public function close(ShiftCloseRequest $request){$shift=$this->shifts->close($request->validated('note'));return redirect()->route('pumper-dashboard-new.operator.dashboard')->with('status',['success'=>1,'msg'=>__('pumperdashboardnew::lang.shift_closed',['shift'=>$shift->shift_number])]);}
    public function print(){ $shift=$this->totals->refresh($this->context->shift(true));$shift->load(['assignments','payments','otherSales','collections']);$this->prints->record('shift',$shift,'shift-summary',request('paper_size'),$shift->closed_statement_printed_at?'reprint':'original');$pumps=$this->masterData->pumps($shift->business_id,$shift->location_id)->keyBy('id');$business=$this->masterData->business($shift->business_id);$location=$this->masterData->location($shift->location_id);return view('pumperdashboardnew::operator.shifts.print',compact('shift','pumps','business','location'));}
}
