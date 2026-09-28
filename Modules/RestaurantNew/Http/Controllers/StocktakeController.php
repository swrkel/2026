<?php
namespace Modules\RestaurantNew\Http\Controllers;
use App\Http\Controllers\Controller;
use Modules\RestaurantNew\Entities\Stocktake;
use Modules\RestaurantNew\Http\Requests\{SaveStocktakeCountsRequest,StoreStocktakeRequest};
use Modules\RestaurantNew\Services\{StocktakeService,TenantScopeService};
class StocktakeController extends Controller
{
 public function index(TenantScopeService $scope){$q=Stocktake::withoutGlobalScopes()->with('lines.ingredient')->where('business_id',$scope->businessId());$scope->applyLocationScope($q);return view('restaurantnew::inventory.stocktakes',['stocktakes'=>$q->latest('id')->paginate(50),'locations'=>$scope->locationOptions()]);}
 public function start(StoreStocktakeRequest $request,StocktakeService $service){$stocktake=$service->start($request->validated());return redirect()->route('restaurant-new.stocktakes.show',$stocktake)->with('success','Stocktake started.');}
 public function show(Stocktake $stocktake,TenantScopeService $scope){$scope->assertBusinessRecord($stocktake,$scope->businessId());return view('restaurantnew::inventory.stocktake-show',['stocktake'=>$stocktake->load('lines.ingredient')]);}
 public function save(SaveStocktakeCountsRequest $request,Stocktake $stocktake,StocktakeService $service){$service->saveCounts($stocktake,$request->validated('counts'));return back()->with('success','Counts saved.');}
 public function post(Stocktake $stocktake,StocktakeService $service){$service->post($stocktake);return back()->with('success','Stocktake variances posted.');}
}
