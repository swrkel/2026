<?php
namespace Modules\RestaurantNew\Http\Controllers;
use App\Http\Controllers\Controller;use Modules\RestaurantNew\Entities\CollectionToken;use Modules\RestaurantNew\Services\CollectionService;use Modules\RestaurantNew\Services\TenantScopeService;
class CollectionController extends Controller
{
 public function index(TenantScopeService $scope){$q=CollectionToken::withoutGlobalScopes()->where('business_id',$scope->businessId())->with('order')->whereIn('status',['queued','ready','called'])->orderByRaw("FIELD(status,'called','ready','queued')")->oldest('created_at');$scope->applyLocationScope($q);return view('restaurantnew::collection.index',['tokens'=>$q->get()]);}
 public function call(CollectionToken $token,CollectionService $service){$service->call($token);return back()->with('success','Token '.$token->token_no.' called.');}
 public function collect(CollectionToken $token,CollectionService $service){$service->collect($token);return back()->with('success','Order collected.');}
}
