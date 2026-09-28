<?php
namespace Modules\RestaurantNew\Http\Controllers;
use App\Http\Controllers\Controller;use Modules\RestaurantNew\Entities\Order;use Modules\RestaurantNew\Http\Requests\SettleOrderRequest;use Modules\RestaurantNew\Services\PaymentService;use Modules\RestaurantNew\Services\ShiftService;use Modules\RestaurantNew\Services\TenantScopeService;
class CashierController extends Controller
{
 public function index(TenantScopeService $scope,ShiftService $shifts){$businessId=$scope->businessId();$orders=Order::withoutGlobalScopes()->where('business_id',$businessId)->with(['table','collectionToken'])->whereNotIn('status',['cancelled','completed'])->latest('id');$scope->applyLocationScope($orders);return view('restaurantnew::cashier.index',['orders'=>$orders->paginate(30),'shift'=>$shifts->current($scope->currentLocationId())]);}
 public function settle(SettleOrderRequest $request,Order $order,PaymentService $service){$order=$service->settle($order,$request->validated()['payments']);return redirect()->route('restaurant-new.print.bill',$order)->with('success','Payment completed.');}
}
