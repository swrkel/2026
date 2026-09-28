<?php
namespace Modules\EggManagement\Http\Controllers;
use Illuminate\Http\Request;use Illuminate\Support\Facades\DB;use Modules\EggManagement\Models\Transfer;use Modules\EggManagement\Models\Grade;use Modules\EggManagement\Services\TransferService;
class TransferController extends BaseController
{
    public function index(){return view('egg::transfers.index',['rows'=>$this->scope(Transfer::query())->latest('transfer_date')->latest('id')->paginate(50)]);}
    public function create(){ $db=DB::connection(config('egg.connection'));$b=$this->context->businessId();$lt=config('egg.common.locations_table','business_locations');$st=config('egg.common.stores_table','stores');$locations=$db->table($lt)->where('business_id',$b)->orderBy('name')->get(['id','name']);$stores=collect();try{$stores=$db->table($st)->where('business_id',$b)->orderBy('name')->get(['id','name']);}catch(\Throwable $e){} return view('egg::transfers.create',['locations'=>$locations,'stores'=>$stores,'grades'=>$this->scope(Grade::query())->where('active',1)->orderBy('sort_order')->get()]); }
    public function store(Request $r,TransferService $svc){$d=$r->validate(['transfer_date'=>'required|date','from_location_id'=>'nullable|integer','from_store_id'=>'nullable|integer','to_location_id'=>'nullable|integer','to_store_id'=>'nullable|integer','note'=>'nullable|max:1000','lines'=>'required|array','lines.*.grade_id'=>'required|integer','lines.*.pieces'=>'required|integer|min:0']);$svc->create($d);return redirect()->route('egg.transfers.index')->with('success','Egg stock transfer completed.');}
}
