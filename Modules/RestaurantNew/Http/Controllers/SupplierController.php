<?php
namespace Modules\RestaurantNew\Http\Controllers;
use App\Http\Controllers\Controller;
use Modules\RestaurantNew\Entities\Supplier;
use Modules\RestaurantNew\Http\Requests\StoreSupplierRequest;
use Modules\RestaurantNew\Services\{ProcurementService,TenantScopeService};
class SupplierController extends Controller
{
 public function index(TenantScopeService $scope){$q=Supplier::withoutGlobalScopes()->where('business_id',$scope->businessId());$scope->applyOptionalLocationScope($q);return view('restaurantnew::procurement.suppliers',['suppliers'=>$q->orderBy('name')->paginate(100),'locations'=>$scope->locationOptions()]);}
 public function store(StoreSupplierRequest $request,ProcurementService $service){$service->supplier($request->validated());return back()->with('success','Supplier saved successfully.');}
}
