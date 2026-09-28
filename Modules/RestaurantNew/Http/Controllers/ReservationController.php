<?php
namespace Modules\RestaurantNew\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\RestaurantNew\Entities\{DiningTable,Reservation};
use Modules\RestaurantNew\Http\Requests\{ReservationStatusRequest,StoreReservationRequest};
use Modules\RestaurantNew\Services\{ReservationService,TenantScopeService};
class ReservationController extends Controller
{
    public function index(Request $request,TenantScopeService $scope){$businessId=$scope->businessId();$locationId=(int)$request->get('location_id')?:null;if($locationId)$scope->assertLocationAccess($locationId);$query=Reservation::withoutGlobalScopes()->with('table')->where('business_id',$businessId)->when($locationId,fn($q)=>$q->where('location_id',$locationId));$scope->applyLocationScope($query);$reservations=$query->when($request->filled('status'),fn($q)=>$q->where('status',$request->get('status')))->when(!$request->filled('from'),fn($q)=>$q->where('reserved_at','>=',now()->subDay()))->when($request->filled('from'),fn($q)=>$q->whereDate('reserved_at','>=',$request->get('from')))->when($request->filled('to'),fn($q)=>$q->whereDate('reserved_at','<=',$request->get('to')))->orderBy('reserved_at')->paginate(100);$tables=DiningTable::withoutGlobalScopes()->where('business_id',$businessId)->where('is_active',true);$scope->applyOptionalLocationScope($tables,'location_id',$locationId);return view('restaurantnew::reservations.index',['reservations'=>$reservations,'tables'=>$tables->orderBy('name')->get(),'locations'=>$scope->locationOptions()]);}
    public function store(StoreReservationRequest $request,ReservationService $service){$service->create($request->validated());return back()->with('success','Reservation created successfully.');}
    public function status(ReservationStatusRequest $request,Reservation $reservation,ReservationService $service){$service->updateStatus($reservation,$request->validated('status'),$request->validated('reason'));return back()->with('success','Reservation status updated.');}
}
