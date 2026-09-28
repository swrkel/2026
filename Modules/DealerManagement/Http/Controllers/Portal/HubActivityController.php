<?php
namespace Modules\DealerManagement\Http\Controllers\Portal;
use App\Http\Controllers\Controller;use Illuminate\Support\Facades\DB;use Modules\DealerManagement\Services\{HubContext,HubDatabaseManager};
class HubActivityController extends Controller
{
 public function deliveries(){ $u=app(HubContext::class)->user();$rows=app(HubDatabaseManager::class)->central(fn()=>DB::table('dlr_hub_delivery_feed as f')->join('dlr_hub_distributors as d','d.id','=','f.distributor_id')->where('f.hub_dealer_id',$u->hub_dealer_id)->select('f.*','d.name as distributor_name')->orderByDesc('f.delivery_at')->simplePaginate(50));return view('dealermanagement::hub.activity.deliveries',compact('rows')); }
 public function returns(){ $u=app(HubContext::class)->user();$rows=app(HubDatabaseManager::class)->central(fn()=>DB::table('dlr_hub_return_feed as f')->join('dlr_hub_distributors as d','d.id','=','f.distributor_id')->where('f.hub_dealer_id',$u->hub_dealer_id)->select('f.*','d.name as distributor_name')->orderByDesc('f.return_at')->simplePaginate(50));return view('dealermanagement::hub.activity.returns',compact('rows')); }
 public function notifications(){ $u=app(HubContext::class)->user();$rows=app(HubDatabaseManager::class)->central(fn()=>DB::table('dlr_hub_notifications')->where('hub_dealer_id',$u->hub_dealer_id)->orderByDesc('id')->simplePaginate(50));return view('dealermanagement::hub.activity.notifications',compact('rows')); }
}
