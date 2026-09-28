<?php
namespace Modules\RestaurantNew\Http\Controllers;
use App\Http\Controllers\Controller;
use Modules\RestaurantNew\Entities\{Ingredient,Wastage};
use Modules\RestaurantNew\Http\Requests\StoreWastageRequest;
use Modules\RestaurantNew\Services\{TenantScopeService,WastageService};
class WastageController extends Controller
{
 public function index(TenantScopeService $scope){$q=Wastage::withoutGlobalScopes()->with('lines.ingredient')->where('business_id',$scope->businessId());$scope->applyLocationScope($q);return view('restaurantnew::inventory.wastage',['wastages'=>$q->latest('id')->paginate(100),'ingredients'=>Ingredient::withoutGlobalScopes()->where('business_id',$scope->businessId())->where('is_active',true)->orderBy('name')->get(),'locations'=>$scope->locationOptions()]);}
 public function store(StoreWastageRequest $request,WastageService $service){$wastage=$service->create($request->validated());return back()->with('success','Wastage '.$wastage->wastage_no.' posted.');}
}
