<?php
namespace Modules\RestaurantNew\Http\Controllers;
use App\Http\Controllers\Controller;
use Modules\RestaurantNew\Entities\{DiscountRule,Order};
use Modules\RestaurantNew\Http\Requests\{ApplyDiscountRequest,StoreDiscountRuleRequest};
use Modules\RestaurantNew\Services\{DiscountService,TenantScopeService};
class DiscountController extends Controller
{
 public function index(TenantScopeService $scope){$q=DiscountRule::withoutGlobalScopes()->where('business_id',$scope->businessId());$scope->applyOptionalLocationScope($q);return view('restaurantnew::discounts.index',['rules'=>$q->latest('id')->paginate(100),'locations'=>$scope->locationOptions()]);}
 public function store(StoreDiscountRuleRequest $request,DiscountService $service){$service->rule($request->validated());return back()->with('success','Discount rule saved.');}
 public function apply(ApplyDiscountRequest $request,Order $order,DiscountService $service,TenantScopeService $scope){$rule=DiscountRule::withoutGlobalScopes()->where('business_id',$scope->businessId())->whereKey((int)$request->validated('discount_rule_id'))->firstOrFail();$service->apply($order,$rule,$request->validated('reason'));return back()->with('success','Discount applied to the order.');}
}
