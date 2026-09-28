<?php
namespace Modules\DealerManagement\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\DealerManagement\Services\DealerContext;
use Modules\DealerManagement\Services\ReorderService;

class OrderController extends Controller
{
    public function index(){ $u=app(DealerContext::class)->user(); $orders=DB::table('dlr_orders')->where('dealer_id',$u->dealer_id)->orderByDesc('id')->simplePaginate(30); return view('dealermanagement::orders.index',compact('orders')); }
    public function create(){ $ctx=app(DealerContext::class); $u=$ctx->user(); $rows=app(ReorderService::class)->rows($u->business_id,$u->dealer_id,$ctx->outletIds()); $outlets=DB::table('dlr_outlets')->whereIn('id',$ctx->outletIds() ?: [0])->get(); return view('dealermanagement::orders.create',compact('rows','outlets')); }
    public function store(Request $r){ $ctx=app(DealerContext::class); $u=$ctx->user(); $d=$r->validate(['outlet_id'=>'required|integer','requested_delivery_date'=>'nullable|date','notes'=>'nullable|string','lines'=>'required|array','lines.*.product_id'=>'required|integer','lines.*.variation_id'=>'nullable|integer','lines.*.product_name'=>'nullable|string','lines.*.current_qty'=>'nullable|numeric','lines.*.suggested_qty'=>'nullable|numeric','lines.*.requested_qty'=>'required|numeric|min:0']); abort_unless(in_array((int)$d['outlet_id'],$ctx->outletIds(),true),403); DB::transaction(function() use($u,$d){ $no='DOR'.now()->format('ymdHis').random_int(10,99); $id=DB::table('dlr_orders')->insertGetId(['business_id'=>$u->business_id,'dealer_id'=>$u->dealer_id,'outlet_id'=>$d['outlet_id'],'order_no'=>$no,'order_date'=>today(),'requested_delivery_date'=>$d['requested_delivery_date']??null,'status'=>'submitted','source'=>'dealer_portal','notes'=>$d['notes']??null,'submitted_by'=>$u->id,'submitted_at'=>now(),'created_at'=>now(),'updated_at'=>now()]); $now=now(); $lineRows=[]; foreach($d['lines'] as $line){ if((float)$line['requested_qty']<=0) continue; $lineRows[]=['order_id'=>$id,'product_id'=>$line['product_id'],'variation_id'=>$line['variation_id']??null,'product_name'=>$line['product_name']??null,'current_qty'=>$line['current_qty']??0,'suggested_qty'=>$line['suggested_qty']??0,'requested_qty'=>$line['requested_qty'],'created_at'=>$now,'updated_at'=>$now]; } if($lineRows) DB::table('dlr_order_lines')->insert($lineRows); DB::table('dlr_integration_outbox')->insert(['business_id'=>$u->business_id,'dealer_id'=>$u->dealer_id,'event_type'=>'dealer_order_submitted','aggregate_type'=>'dlr_order','aggregate_id'=>$id,'payload_json'=>json_encode(['order_id'=>$id,'order_no'=>$no]),'status'=>'pending','created_at'=>now(),'updated_at'=>now()]); }); return redirect()->route('dealermanagement.portal.orders.index')->with('status','Order request submitted.'); }
}
