<?php
namespace Modules\DealerManagement\Http\Controllers\Portal;
use App\Http\Controllers\Controller;use Illuminate\Http\Request;use Illuminate\Support\Facades\DB;use Modules\DealerManagement\Services\{HubContext,HubConnectionService,HubDatabaseManager,HubFeedService};
class HubConnectionController extends Controller
{
 public function index(){ $u=app(HubContext::class)->user();$rows=app(HubDatabaseManager::class)->central(fn()=>DB::table('dlr_hub_connections as c')->join('dlr_hub_distributors as d','d.id','=','c.distributor_id')->where('c.hub_dealer_id',$u->hub_dealer_id)->select('c.*','d.distributor_code','d.name as distributor_name')->orderBy('d.name')->get());return view('dealermanagement::hub.connections',compact('rows')); }
 public function approve(Request $r){$d=$r->validate(['invitation_code'=>'required|string|max:12']);$u=app(HubContext::class)->user();app(HubConnectionService::class)->approveByCode($u->hub_dealer_id,$d['invitation_code'],$u->id);app(HubFeedService::class)->refresh($u->hub_dealer_id);return back()->with('status','Distributor connection approved.');}
 public function request(Request $r){$d=$r->validate(['distributor_code'=>'required|string|max:30']);$u=app(HubContext::class)->user();app(HubConnectionService::class)->requestByDistributorCode($u->hub_dealer_id,$d['distributor_code']);return back()->with('status','Connection request submitted to the distributor.');}
 public function refresh(){ $u=app(HubContext::class)->user();$r=app(HubFeedService::class)->refresh($u->hub_dealer_id);return back()->with('status','Hub data refreshed. Deliveries: '.$r['deliveries'].'; Returns: '.$r['returns'].'.');}
}
