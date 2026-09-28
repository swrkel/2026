<?php
namespace Modules\RestaurantNew\Http\Controllers;
use App\Http\Controllers\Controller;use Modules\RestaurantNew\Entities\Order;use Modules\RestaurantNew\Services\TenantScopeService;
class TakeawayController extends Controller { public function index(TenantScopeService $scope){$q=Order::withoutGlobalScopes()->where('business_id',$scope->businessId())->with(['items','collectionToken'])->where('order_type','takeaway')->whereNotIn('status',['cancelled','completed'])->latest('id');$scope->applyLocationScope($q);return view('restaurantnew::takeaway.index',['orders'=>$q->paginate(30)]);} }
