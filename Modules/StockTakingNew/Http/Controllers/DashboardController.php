<?php
namespace Modules\StockTakingNew\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StockTakingNew\Entities\StockTakeSession;
use Modules\StockTakingNew\Services\TenantScopeService;
class DashboardController extends Controller
{
 public function index(Request $request,TenantScopeService $scope)
 {
  $businessId=$scope->businessId($request); abort_unless($businessId,403);
  $base=StockTakeSession::where('business_id',$businessId); $scope->applyLocationScope($base);
  $stats=['draft'=>(clone $base)->where('status','draft')->count(),'counting'=>(clone $base)->whereIn('status',['prepared','counting','recount'])->count(),
   'awaiting_approval'=>(clone $base)->where('status','submitted')->count(),'approved'=>(clone $base)->where('status','approved')->count(),
   'posted'=>(clone $base)->where('status','posted')->count(),'variance_value'=>(float)(clone $base)->whereIn('status',['submitted','approved','posted'])->sum('variance_value_total')];
  $latest=(clone $base)->latest('count_date')->latest('id')->limit(10)->get();
  $attention=(clone $base)->where(function($q){$q->where('status','submitted')->orWhere(function($x){$x->whereIn('status',['counting','recount'])->whereColumn('counted_line_count','<','line_count');});})->latest('id')->limit(8)->get();
  return view('stocktakingnew::dashboard.index',compact('stats','latest','attention'));
 }
}
