<?php
namespace Modules\StockTakingNew\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StockTakingNew\Entities\StockTakeSession;
use Modules\StockTakingNew\Services\ApprovalService;
use Modules\StockTakingNew\Services\TenantScopeService;
class ApprovalController extends Controller
{
 public function index(Request $request,TenantScopeService $scope)
 { $businessId=$scope->businessId($request);abort_unless($businessId,403);$query=StockTakeSession::where('business_id',$businessId);$scope->applyLocationScope($query);$sessions=$query->whereIn('status',['submitted','approved'])->latest('submitted_at')->paginate(30);return view('stocktakingnew::approvals.index',compact('sessions')); }
 public function approve(StockTakeSession $session,Request $request,TenantScopeService $scope,ApprovalService $service)
 { $scope->assertBusinessRecord($session,$scope->businessId($request));$request->validate(['remarks'=>'nullable|string|max:3000']);try{$service->approve($session,$request->remarks);return back()->with('status','Session approved.');}catch(\Throwable $e){return back()->withErrors($e->getMessage());} }
 public function reject(StockTakeSession $session,Request $request,TenantScopeService $scope,ApprovalService $service)
 { $scope->assertBusinessRecord($session,$scope->businessId($request));$request->validate(['remarks'=>'required|string|max:3000']);try{$service->reject($session,$request->remarks);return back()->with('status','Session rejected.');}catch(\Throwable $e){return back()->withErrors($e->getMessage());} }
 public function post(StockTakeSession $session,Request $request,TenantScopeService $scope,ApprovalService $service)
 { $scope->assertBusinessRecord($session,$scope->businessId($request));try{$count=$service->post($session);return back()->with('status',"Reconciliation posted for {$count} product lines.");}catch(\Throwable $e){return back()->withErrors($e->getMessage());} }
}
