<?php
namespace Modules\PumperDashboardNew\Http\Controllers\Operator;

use Modules\PumperDashboardNew\Entities\PoneDailyCollection;
use Modules\PumperDashboardNew\Entities\PonePrintLog;
use Modules\PumperDashboardNew\Http\Controllers\Controller;
use Modules\PumperDashboardNew\Http\Requests\CollectionStoreRequest;
use Modules\PumperDashboardNew\Http\Requests\VoidRequest;
use Modules\PumperDashboardNew\Services\PoneCollectionService;
use Modules\PumperDashboardNew\Services\PoneContextService;
use Modules\PumperDashboardNew\Services\PonePrintService;
use Modules\PumperDashboardNew\Services\PoneSharedMasterDataService;

class CollectionController extends Controller
{
    public function __construct(private PoneContextService $context,private PoneCollectionService $collections,private PonePrintService $prints,private PoneSharedMasterDataService $masterData) {}
    public function index(){ $shift=$this->context->shift(); $breakdown=$this->collections->breakdown(); $collections=PoneDailyCollection::query()->where('shift_id',$shift->id)->latest('collection_at')->latest('id')->get(); return view('pumperdashboardnew::operator.collections.index',compact('shift','breakdown','collections')); }
    public function store(CollectionStoreRequest $request){$collection=$this->collections->create($request->validated());return redirect()->route('pumper-dashboard-new.operator.collections.show',$collection)->with('status',['success'=>1,'msg'=>__('pumperdashboardnew::lang.collection_saved')]);}
    public function show(int $collection){$shift=$this->context->shift(true);$collection=$this->find($collection,$shift->id);return view('pumperdashboardnew::operator.collections.show',compact('shift','collection'));}
    public function destroy(VoidRequest $request,int $collection){$this->collections->void($collection,$request->validated('reason'));return $this->ok(__('pumperdashboardnew::lang.collection_voided'));}
    public function print(int $collection){$shift=$this->context->shift(true);$collection=$this->find($collection,$shift->id);$hasPrinted=PonePrintLog::query()->where('printable_type','daily_collection')->where('printable_id',$collection->id)->exists();$copyType=$hasPrinted?'reprint':'original';$this->prints->record('daily_collection',$collection,'daily-collection',request('paper_size'),$copyType);$business=$this->masterData->business($shift->business_id);$location=$this->masterData->location($shift->location_id);return view('pumperdashboardnew::operator.collections.print',compact('shift','collection','business','location','copyType'));}
    private function find(int $id,int $shiftId):PoneDailyCollection{return PoneDailyCollection::query()->whereKey($id)->where('shift_id',$shiftId)->where('business_id',$this->context->businessId())->firstOrFail();}
}
