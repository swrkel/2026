<?php
namespace Modules\AutoService\Http\Controllers;
use Illuminate\Http\Request;
use Modules\AutoService\Entities\AutoServiceJob;
use Modules\AutoService\Entities\AutoServiceMechanic;
use Modules\AutoService\Services\AutoServiceWorkshopService;
use Modules\AutoService\Services\ProductPartsAdapter;
class WorkshopController extends AutoServiceBaseController
{
    public function index(){ $q=AutoServiceJob::with(['mechanics.mechanic','partMovements']); if($this->businessId())$q->where('business_id',$this->businessId()); return view('autoservice::workshop.index',['jobs'=>$q->orderByDesc('id')->paginate(25)]); }
    public function job($id){ $job=AutoServiceJob::with(['lines','payments','mechanics.mechanic','partMovements'])->findOrFail($id); return view('autoservice::workshop.job',['job'=>$job,'mechanics'=>AutoServiceMechanic::where('business_id',$this->businessId())->where('is_active',1)->orderBy('name')->get(),'products'=>app(ProductPartsAdapter::class)->listForSelect($this->businessId())]); }
    public function status(Request $r,$id){ app(AutoServiceWorkshopService::class)->changeStatus(AutoServiceJob::findOrFail($id),$r->input('status','in_progress'),$r->input('note')); return back()->with('status','Job status updated.'); }
    public function mechanics(Request $r,$id){ app(AutoServiceWorkshopService::class)->assignMechanics(AutoServiceJob::findOrFail($id),$r->input('mechanics',[])); return back()->with('status','Mechanics updated.'); }
    public function parts(Request $r,$id){ app(AutoServiceWorkshopService::class)->savePartMovements(AutoServiceJob::findOrFail($id),$r->input('parts',[])); return back()->with('status','Parts updated.'); }
}
