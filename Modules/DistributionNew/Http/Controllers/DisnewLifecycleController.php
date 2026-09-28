<?php
namespace Modules\DistributionNew\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Models\DisnewSalesOrder;
use Modules\DistributionNew\Services\DisnewOrderLifecycleService;
class DisnewLifecycleController extends Controller { public function update(Request $request, $id, DisnewOrderLifecycleService $service){ $order=DisnewSalesOrder::findOrFail($id); $service->changeStatus($order, $request->status, auth()->id(), $request->remarks); return back()->with('status','Order status updated.'); } }
