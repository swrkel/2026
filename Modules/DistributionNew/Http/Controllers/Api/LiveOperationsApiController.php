<?php
namespace Modules\DistributionNew\Http\Controllers\Api;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Services\LiveOperations\LiveOperationsApiService;
class LiveOperationsApiController extends Controller
{
 public function driverStatus(Request $r, LiveOperationsApiService $s){ return response()->json($s->saveDriverStatus($r)); }
 public function vehicleLocation(Request $r, LiveOperationsApiService $s){ return response()->json($s->saveVehicleLocation($r)); }
 public function deliveryTimeline(Request $r, LiveOperationsApiService $s){ return response()->json($s->saveDeliveryTimeline($r)); }
}
