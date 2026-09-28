<?php
namespace Modules\DistributionNew\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Services\DisnewMobileSyncService;

class DisnewMobileApiController extends Controller
{
    public function registerDevice(Request $request, DisnewMobileSyncService $sync)
    {
        return response()->json(['success' => true, 'device' => $sync->registerDevice($request->all())]);
    }

    public function push(Request $request, DisnewMobileSyncService $sync)
    {
        $saved = $sync->pushOfflineQueue($request->only(['business_id','location_id','user_id','device_id']), $request->input('items', []));
        return response()->json(['success' => true, 'queued' => count($saved)]);
    }

    public function pull(Request $request)
    {
        return response()->json(['success' => true, 'server_time' => now(), 'data' => []]);
    }
}
