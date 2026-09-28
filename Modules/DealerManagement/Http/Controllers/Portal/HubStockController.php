<?php
namespace Modules\DealerManagement\Http\Controllers\Portal;
use App\Http\Controllers\Controller;use Illuminate\Http\Request;use Illuminate\Support\Facades\DB;use Modules\DealerManagement\Services\{HubContext,HubDatabaseManager,HubProductService};
class HubStockController extends Controller
{
 public function index(Request $r){$ctx=app(HubContext::class);$u=$ctx->user();$dist=(int)$r->get('distributor_id',0)?:null;$rows=app(HubProductService::class)->consolidatedStock($u->hub_dealer_id,$ctx->outletIds(),$dist);$distributors=app(HubDatabaseManager::class)->central(fn()=>DB::table('dlr_hub_connections as c')->join('dlr_hub_distributors as d','d.id','=','c.distributor_id')->where('c.hub_dealer_id',$u->hub_dealer_id)->where('c.status','active')->pluck('d.name','d.id'));return view('dealermanagement::hub.stock',compact('rows','distributors','dist')); }
}
