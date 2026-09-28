<?php
namespace Modules\DealerManagement\Http\Controllers\Portal;
use App\Http\Controllers\Controller; use Illuminate\Support\Facades\DB; use Modules\DealerManagement\Services\DealerContext;
class ReportController extends Controller { public function index(){ $ctx=app(DealerContext::class); $u=$ctx->user(); $summary=['products'=>DB::table('dlr_stock_balances')->where('dealer_id',$u->dealer_id)->whereIn('outlet_id',$ctx->outletIds() ?: [0])->count(),'updates'=>DB::table('dlr_stock_updates')->where('dealer_id',$u->dealer_id)->count(),'orders'=>DB::table('dlr_orders')->where('dealer_id',$u->dealer_id)->count(),'alerts'=>DB::table('dlr_notifications')->where('dealer_id',$u->dealer_id)->where('is_read',0)->count()]; return view('dealermanagement::reports.index',compact('summary')); } }
