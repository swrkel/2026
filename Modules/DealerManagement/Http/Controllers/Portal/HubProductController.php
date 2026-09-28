<?php
namespace Modules\DealerManagement\Http\Controllers\Portal;
use App\Http\Controllers\Controller;use Illuminate\Http\Request;use Illuminate\Support\Facades\DB;use Modules\DealerManagement\Services\{HubContext,HubDatabaseManager,HubNotificationService};
class HubProductController extends Controller
{
 public function sources(){ $u=app(HubContext::class)->user();$rows=app(HubDatabaseManager::class)->central(fn()=>DB::table('dlr_hub_product_sources as s')->join('dlr_hub_distributors as d','d.id','=','s.distributor_id')->join('dlr_hub_outlets as o','o.id','=','s.hub_outlet_id')->where('s.hub_dealer_id',$u->hub_dealer_id)->select('s.*','d.name as distributor_name','o.name as outlet_name')->orderBy('s.product_name')->orderBy('d.name')->simplePaginate(75));return view('dealermanagement::hub.products.sources',compact('rows')); }
 public function save(Request $r){$u=app(HubContext::class)->user();$d=$r->validate(['source_id'=>'required|integer','allocation_method'=>'required|in:fifo,proportional,manual','reorder_level'=>'required|numeric|min:0']);app(HubDatabaseManager::class)->central(fn()=>DB::table('dlr_hub_product_sources')->where('hub_dealer_id',$u->hub_dealer_id)->where('id',$d['source_id'])->update(['allocation_method'=>$d['allocation_method'],'reorder_level'=>$d['reorder_level'],'updated_at'=>now()]));app(HubNotificationService::class)->refreshReorderAlerts($u->hub_dealer_id);return back()->with('status','Product source settings updated.');}
}
