<?php
namespace Modules\RestaurantNew\Http\Controllers;
use App\Http\Controllers\Controller;use Illuminate\Http\Request;use Modules\RestaurantNew\Entities\KitchenStation;use Modules\RestaurantNew\Entities\KitchenTicket;use Modules\RestaurantNew\Http\Requests\KitchenStatusRequest;use Modules\RestaurantNew\Services\KitchenService;use Modules\RestaurantNew\Services\TenantScopeService;
class KitchenController extends Controller
{
 public function index(Request $request,TenantScopeService $scope){$businessId=$scope->businessId();$station=(int)$request->get('station_id',0);$tickets=KitchenTicket::withoutGlobalScopes()->where('business_id',$businessId)->with(['order.table','order.collectionToken','items'])->whereIn('status',['new','accepted','preparing'])->when($station,fn($q)=>$q->where('station_id',$station))->oldest('created_at');$scope->applyLocationScope($tickets);$stationsQuery=KitchenStation::where('is_active',true);$stations=$scope->applyOptionalLocationScope($stationsQuery)->orderBy('sort_order')->get();if($station&&!$stations->contains('id',$station))$station=0;return view('restaurantnew::kitchen.index',compact('tickets','stations','station'));}
 public function status(KitchenStatusRequest $request,KitchenTicket $ticket,KitchenService $service){$service->updateStatus($ticket,$request->validated()['status']);return back()->with('success','Kitchen ticket updated.');}
}
