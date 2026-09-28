<?php
namespace Modules\DealerManagement\Http\Controllers\Portal;
use App\Http\Controllers\Controller;use Modules\DealerManagement\Services\{HubContext,HubProductService};
class HubReorderController extends Controller
{
 public function index(){ $ctx=app(HubContext::class);$u=$ctx->user();$rows=app(HubProductService::class)->consolidatedStock($u->hub_dealer_id,$ctx->outletIds())->filter(fn($r)=>(float)$r->total_reorder_level>0&&(float)$r->total_qty<=(float)$r->total_reorder_level);return view('dealermanagement::hub.reorder',compact('rows')); }
}
