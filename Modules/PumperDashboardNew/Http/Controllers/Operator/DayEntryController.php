<?php
namespace Modules\PumperDashboardNew\Http\Controllers\Operator;

use Modules\PumperDashboardNew\Entities\PoneDayEntry;
use Modules\PumperDashboardNew\Http\Controllers\Controller;
use Modules\PumperDashboardNew\Http\Requests\DayEntryStoreRequest;
use Modules\PumperDashboardNew\Http\Requests\VoidRequest;
use Modules\PumperDashboardNew\Services\PoneContextService;
use Modules\PumperDashboardNew\Services\PoneDayEntryService;
use Modules\PumperDashboardNew\Services\PoneSharedMasterDataService;

class DayEntryController extends Controller
{
    public function __construct(private PoneContextService $context,private PoneDayEntryService $entries,private PoneSharedMasterDataService $masterData) {}
    public function index(){ $shift=$this->context->shift(); $entries=PoneDayEntry::query()->where('shift_id',$shift->id)->latest('entry_at')->latest('id')->get(); $assignments=$shift->assignments()->orderBy('pump_id')->get(); $pumps=$this->masterData->pumps($shift->business_id,$shift->location_id)->keyBy('id'); $references=$shift->settlementReferences()->latest('settlement_date')->get(); return view('pumperdashboardnew::operator.day-entries.index',compact('shift','entries','assignments','pumps','references')); }
    public function store(DayEntryStoreRequest $request){$this->entries->create($request->validated());return $this->ok(__('pumperdashboardnew::lang.day_entry_saved'));}
    public function edit(int $entry){$shift=$this->context->shift();$entry=$this->find($entry,$shift->id);$assignments=$shift->assignments()->orderBy('pump_id')->get();$pumps=$this->masterData->pumps($shift->business_id,$shift->location_id)->keyBy('id');return view('pumperdashboardnew::operator.day-entries.edit',compact('shift','entry','assignments','pumps'));}
    public function update(DayEntryStoreRequest $request,int $entry){$this->entries->update($entry,$request->validated());return redirect()->route('pumper-dashboard-new.operator.day-entries.index')->with('status',['success'=>1,'msg'=>__('pumperdashboardnew::lang.day_entry_updated')]);}
    public function destroy(VoidRequest $request,int $entry){$this->entries->void($entry,$request->validated('reason'));return $this->ok(__('pumperdashboardnew::lang.day_entry_voided'));}
    private function find(int $id,int $shiftId):PoneDayEntry{return PoneDayEntry::query()->whereKey($id)->where('shift_id',$shiftId)->where('business_id',$this->context->businessId())->firstOrFail();}
}
