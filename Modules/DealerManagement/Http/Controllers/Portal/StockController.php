<?php
namespace Modules\DealerManagement\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\DealerManagement\Services\DealerContext;
use Modules\DealerManagement\Services\ReorderService;
use Modules\DealerManagement\Services\NotificationService;

class StockController extends Controller
{
    public function index(Request $r){ $ctx=app(DealerContext::class); $u=$ctx->user(); $rows=app(ReorderService::class)->rows($u->business_id,$u->dealer_id,$ctx->outletIds()); $outlets=DB::table('dlr_outlets')->whereIn('id',$ctx->outletIds() ?: [0])->pluck('name','id'); return view('dealermanagement::stock.index',compact('rows','outlets')); }
    public function updateForm(){ $ctx=app(DealerContext::class); $u=$ctx->user(); $balances=DB::table('dlr_stock_balances')->where('dealer_id',$u->dealer_id)->whereIn('outlet_id',$ctx->outletIds() ?: [0])->orderBy('product_name')->get(); $outlets=DB::table('dlr_outlets')->whereIn('id',$ctx->outletIds() ?: [0])->orderBy('name')->get(); return view('dealermanagement::stock.update',compact('balances','outlets')); }
    public function submitUpdate(Request $r){
        $ctx=app(DealerContext::class); $u=$ctx->user();
        $d=$r->validate(['outlet_id'=>'required|integer','lines'=>'required|array','lines.*.balance_id'=>'required|integer','lines.*.physical_qty'=>'required|numeric|min:0','lines.*.sold_qty'=>'nullable|numeric|min:0','lines.*.damaged_qty'=>'nullable|numeric|min:0','notes'=>'nullable|string']);
        $allowedOutlets=$ctx->outletIds(); abort_unless(in_array((int)$d['outlet_id'],$allowedOutlets,true),403);
        $balanceIds=array_values(array_unique(array_map('intval',array_column($d['lines'],'balance_id'))));
        $balances=DB::table('dlr_stock_balances')->where('dealer_id',$u->dealer_id)->where('outlet_id',$d['outlet_id'])->whereIn('id',$balanceIds)->get()->keyBy('id');
        DB::transaction(function() use($u,$d,$balances){
            $now=now(); $no='DSU'.$now->format('YmdHis').random_int(10,99);
            $updateId=DB::table('dlr_stock_updates')->insertGetId(['business_id'=>$u->business_id,'dealer_id'=>$u->dealer_id,'outlet_id'=>$d['outlet_id'],'update_no'=>$no,'update_date'=>$now->toDateString(),'notes'=>$d['notes']??null,'submitted_by'=>$u->id,'submitted_at'=>$now,'created_at'=>$now,'updated_at'=>$now]);
            $lineRows=[]; $movementRows=[];
            foreach($d['lines'] as $line){
                $b=$balances->get((int)$line['balance_id']); if(!$b) continue;
                $physical=(float)$line['physical_qty']; $before=(float)$b->effective_qty; $variance=$physical-$before;
                $lineRows[]=['stock_update_id'=>$updateId,'product_id'=>$b->product_id,'variation_id'=>$b->variation_id,'system_qty'=>$before,'physical_qty'=>$physical,'variance_qty'=>$variance,'sold_qty'=>$line['sold_qty']??0,'damaged_qty'=>$line['damaged_qty']??0,'created_at'=>$now,'updated_at'=>$now];
                DB::table('dlr_stock_balances')->where('id',$b->id)->update(['system_qty'=>$physical,'confirmed_qty'=>$physical,'effective_qty'=>$physical,'last_confirmed_at'=>$now,'last_movement_at'=>$now,'updated_at'=>$now]);
                $movementRows[]=['business_id'=>$u->business_id,'dealer_id'=>$u->dealer_id,'outlet_id'=>$d['outlet_id'],'product_id'=>$b->product_id,'variation_id'=>$b->variation_id,'product_name'=>$b->product_name,'movement_type'=>'physical_confirmation','direction'=>'set','qty'=>$physical,'qty_before'=>$before,'qty_after'=>$physical,'reference_type'=>'dlr_stock_update','reference_id'=>$updateId,'reference_no'=>$no,'notes'=>null,'created_by_type'=>'dealer_user','created_by_id'=>$u->id,'movement_at'=>$now,'created_at'=>$now,'updated_at'=>$now];
            }
            if($lineRows) DB::table('dlr_stock_update_lines')->insert($lineRows);
            if($movementRows) DB::table('dlr_stock_movements')->insert($movementRows);
        });
        app(NotificationService::class)->refresh((int)$u->business_id,(int)$u->dealer_id,$allowedOutlets);
        return redirect()->route('dealermanagement.portal.stock.index')->with('status','Stock update submitted successfully.');
    }
    public function history(Request $r){ $ctx=app(DealerContext::class); $u=$ctx->user(); $moves=DB::table('dlr_stock_movements')->where('dealer_id',$u->dealer_id)->whereIn('outlet_id',$ctx->outletIds() ?: [0])->orderByDesc('movement_at')->simplePaginate(50); return view('dealermanagement::stock.history',compact('moves')); }
}
