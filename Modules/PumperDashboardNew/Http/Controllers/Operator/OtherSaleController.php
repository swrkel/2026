<?php
namespace Modules\PumperDashboardNew\Http\Controllers\Operator;

use Modules\PumperDashboardNew\Entities\PoneOtherSale;
use Modules\PumperDashboardNew\Http\Controllers\Controller;
use Modules\PumperDashboardNew\Http\Requests\OtherSaleStoreRequest;
use Modules\PumperDashboardNew\Http\Requests\VoidRequest;
use Modules\PumperDashboardNew\Services\PoneContextService;
use Modules\PumperDashboardNew\Services\PoneOtherSaleService;
use Modules\PumperDashboardNew\Services\PonePrintService;
use Modules\PumperDashboardNew\Services\PoneSharedMasterDataService;

class OtherSaleController extends Controller
{
    public function __construct(private PoneContextService $context, private PoneOtherSaleService $sales,
        private PoneSharedMasterDataService $masterData, private PonePrintService $prints) {}

    public function create() { $shift=$this->context->shift(); return view('pumperdashboardnew::operator.other-sales.create',$this->formData($shift)); }
    public function store(OtherSaleStoreRequest $request) { $sale=$this->sales->create($request->validated()); return redirect()->route('pumper-dashboard-new.operator.other-sales.show',$sale)->with('status',['success'=>1,'msg'=>__('pumperdashboardnew::lang.other_sale_saved')]); }
    public function index() { $shift=$this->context->shift(); $sales=PoneOtherSale::query()->where('shift_id',$shift->id)->with('lines')->latest('sale_at')->latest('id')->get(); return view('pumperdashboardnew::operator.other-sales.index',compact('shift','sales')); }
    public function show(int $sale) { $shift=$this->context->shift(true); $sale=$this->find($sale,$shift->id); $products=$this->masterData->products($shift->business_id,$shift->location_id,null,10000)->keyBy('id'); $customers=$this->masterData->customers($shift->business_id,null,10000)->keyBy('id'); return view('pumperdashboardnew::operator.other-sales.show',compact('shift','sale','products','customers')); }
    public function edit(int $sale) { $shift=$this->context->shift(); $sale=$this->find($sale,$shift->id); return view('pumperdashboardnew::operator.other-sales.edit',$this->formData($shift)+compact('sale')); }
    public function update(OtherSaleStoreRequest $request,int $sale) { $sale=$this->sales->update($sale,$request->validated()); return redirect()->route('pumper-dashboard-new.operator.other-sales.show',$sale)->with('status',['success'=>1,'msg'=>__('pumperdashboardnew::lang.other_sale_updated')]); }
    public function destroy(VoidRequest $request,int $sale) { $this->sales->void($sale,$request->validated('reason')); return $this->ok(__('pumperdashboardnew::lang.other_sale_voided')); }
    public function print(int $sale) { $shift=$this->context->shift(true); $sale=$this->find($sale,$shift->id); $copyType=$sale->printed_count>0?'reprint':'original'; $this->prints->record('other_sale',$sale,'other-sale-receipt',request('paper_size'),$copyType); $products=$this->masterData->products($shift->business_id,$shift->location_id,null,10000)->keyBy('id'); $business=$this->masterData->business($shift->business_id); $location=$this->masterData->location($shift->location_id); return view('pumperdashboardnew::operator.other-sales.print',compact('shift','sale','products','business','location','copyType')); }
    private function formData($shift): array { return ['shift'=>$shift,'products'=>$this->masterData->products($shift->business_id,$shift->location_id),'stores'=>$this->masterData->stores($shift->business_id,$shift->location_id),'customers'=>$this->masterData->customers($shift->business_id)]; }
    private function find(int $id,int $shiftId): PoneOtherSale { return PoneOtherSale::query()->whereKey($id)->where('shift_id',$shiftId)->where('business_id',$this->context->businessId())->with('lines')->firstOrFail(); }
}
