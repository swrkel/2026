<?php
namespace Modules\PumperDashboardNew\Http\Controllers\Operator;

use Modules\PumperDashboardNew\Entities\PoneUnloadStock;
use Modules\PumperDashboardNew\Http\Controllers\Controller;
use Modules\PumperDashboardNew\Http\Requests\UnloadStockStoreRequest;
use Modules\PumperDashboardNew\Http\Requests\VoidRequest;
use Modules\PumperDashboardNew\Services\PoneContextService;
use Modules\PumperDashboardNew\Services\PonePrintService;
use Modules\PumperDashboardNew\Services\PoneSharedMasterDataService;
use Modules\PumperDashboardNew\Services\PoneUnloadStockService;

class UnloadStockController extends Controller
{
    public function __construct(private PoneContextService $context,private PoneUnloadStockService $unloads,private PoneSharedMasterDataService $masterData,private PonePrintService $prints) {}
    public function create(){ $shift=$this->context->shift(); return view('pumperdashboardnew::operator.unload-stock.create',$this->formData($shift)); }
    public function store(UnloadStockStoreRequest $request){ $unload=$this->unloads->create($request->validated()); return redirect()->route('pumper-dashboard-new.operator.unload-stock.show',$unload)->with('status',['success'=>1,'msg'=>__('pumperdashboardnew::lang.unload_saved')]); }
    public function index(){ $shift=$this->context->shift(); $unloads=PoneUnloadStock::query()->where('shift_id',$shift->id)->with('lines')->latest('unloaded_at')->latest('id')->get(); return view('pumperdashboardnew::operator.unload-stock.index',compact('shift','unloads')); }
    public function show(int $unload){ $shift=$this->context->shift(true); $unload=$this->find($unload,$shift->id); $products=$this->masterData->products($shift->business_id,$shift->location_id,null,10000)->keyBy('id'); $suppliers=$this->masterData->suppliers($shift->business_id,null,10000)->keyBy('id'); return view('pumperdashboardnew::operator.unload-stock.show',compact('shift','unload','products','suppliers')); }
    public function edit(int $unload){ $shift=$this->context->shift(); $unload=$this->find($unload,$shift->id); return view('pumperdashboardnew::operator.unload-stock.edit',$this->formData($shift)+compact('unload')); }
    public function update(UnloadStockStoreRequest $request,int $unload){ $unload=$this->unloads->update($unload,$request->validated()); return redirect()->route('pumper-dashboard-new.operator.unload-stock.show',$unload)->with('status',['success'=>1,'msg'=>__('pumperdashboardnew::lang.unload_updated')]); }
    public function destroy(VoidRequest $request,int $unload){ $this->unloads->void($unload,$request->validated('reason')); return $this->ok(__('pumperdashboardnew::lang.unload_voided')); }
    public function print(int $unload){ $shift=$this->context->shift(true); $unload=$this->find($unload,$shift->id); $copyType=$unload->printed_count>0?'reprint':'original'; $this->prints->record('unload_stock',$unload,'unload-stock-receipt',request('paper_size'),$copyType); $products=$this->masterData->products($shift->business_id,$shift->location_id,null,10000)->keyBy('id'); $business=$this->masterData->business($shift->business_id); $location=$this->masterData->location($shift->location_id); return view('pumperdashboardnew::operator.unload-stock.print',compact('shift','unload','products','business','location','copyType')); }
    private function formData($shift):array{return ['shift'=>$shift,'products'=>$this->masterData->products($shift->business_id,$shift->location_id),'stores'=>$this->masterData->stores($shift->business_id,$shift->location_id),'tanks'=>$this->masterData->tanks($shift->business_id,$shift->location_id),'suppliers'=>$this->masterData->suppliers($shift->business_id)];}
    private function find(int $id,int $shiftId):PoneUnloadStock{return PoneUnloadStock::query()->whereKey($id)->where('shift_id',$shiftId)->where('business_id',$this->context->businessId())->with('lines')->firstOrFail();}
}
