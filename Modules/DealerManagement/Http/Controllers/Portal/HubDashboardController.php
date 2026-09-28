<?php
namespace Modules\DealerManagement\Http\Controllers\Portal;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Modules\DealerManagement\Services\{HubContext,HubDatabaseManager,HubProductService};
class HubDashboardController extends Controller
{
 public function index(){ $ctx=app(HubContext::class);$u=$ctx->user();$d=$ctx->dealer();$stats=app(HubDatabaseManager::class)->central(function()use($u,$ctx){return ['distributors'=>DB::table('dlr_hub_connections')->where('hub_dealer_id',$u->hub_dealer_id)->where('status','active')->count(),'products'=>DB::table('dlr_hub_product_sources')->where('hub_dealer_id',$u->hub_dealer_id)->where('is_active',1)->distinct('product_key')->count('product_key'),'orders'=>DB::table('dlr_hub_orders')->where('hub_dealer_id',$u->hub_dealer_id)->whereIn('status',['submitted','pending'])->count(),'alerts'=>DB::table('dlr_hub_notifications')->where('hub_dealer_id',$u->hub_dealer_id)->where('is_read',0)->count()];});$stock=app(HubProductService::class)->consolidatedStock($u->hub_dealer_id,$ctx->outletIds())->take(8);return view('dealermanagement::hub.dashboard',compact('u','d','stats','stock')); }
}
