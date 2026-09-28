<?php
namespace Modules\DistributionNew\Http\Controllers\Audit;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Services\Audit\DisnewProductionAuditService;

class DisnewProductionAuditController extends Controller{
 public function __construct(private DisnewProductionAuditService $service){}
 public function index(){return view('distributionnew::audit.index',['summary'=>$this->service->summary()]);}
 public function permissions(){return view('distributionnew::audit.permissions',['rows'=>$this->service->permissionChecks()]);}
 public function menu(){return view('distributionnew::audit.menu',['rows'=>$this->service->menuChecks()]);}
 public function routes(){return view('distributionnew::audit.routes',['rows'=>$this->service->routeChecks()]);}
 public function sql(){return view('distributionnew::audit.sql',['rows'=>$this->service->sqlChecks()]);}
 public function ui(){return view('distributionnew::audit.ui',['rows'=>$this->service->uiChecks()]);}
 public function run(Request $request){$result=$this->service->runFullAudit($request->user());return redirect()->route('distributionnew.audit.index')->with('status',$result['message']);}
}
