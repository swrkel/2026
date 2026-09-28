<?php

namespace Modules\DistributionNew\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Services\Sync\DisnewOfflineSyncService;

class DisnewOfflineSyncController extends Controller
{
    public function push(Request $request, DisnewOfflineSyncService $service){ return response()->json($service->push($request)); }
    public function pull(Request $request, DisnewOfflineSyncService $service){ return response()->json($service->pull($request)); }
    public function batches(Request $request, DisnewOfflineSyncService $service){ return response()->json($service->batches($request)); }
    public function adminBatches(DisnewOfflineSyncService $service){ return view('distributionnew::sync.batches', ['batches' => $service->latestBatches()]); }
    public function adminDevices(DisnewOfflineSyncService $service){ return view('distributionnew::sync.devices', ['devices' => $service->latestDevices()]); }
}
