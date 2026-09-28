<?php
namespace Modules\DistributionNew\Http\Controllers\LiveOperations;
use Illuminate\Routing\Controller;
class DispatchMonitorController extends Controller { public function index(){ return view('distributionnew::live_operations.dispatch_monitor.index'); } }
