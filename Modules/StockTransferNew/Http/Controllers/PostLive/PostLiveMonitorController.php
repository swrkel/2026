<?php

namespace Modules\StockTransferNew\Http\Controllers\PostLive;

use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Services\PostLive\PostLiveMonitorService;

class PostLiveMonitorController extends Controller
{
    public function index(PostLiveMonitorService $service)
    {
        $businessId = session('business.id') ?? request()->session()->get('business.id');
        $summary = $service->summary($businessId ? (int) $businessId : null);
        $exceptions = $service->exceptions($businessId ? (int) $businessId : null);
        $activity = $service->latestActivity($businessId ? (int) $businessId : null);

        return view('stocktransfernew::post_live.monitor', compact('summary', 'exceptions', 'activity'));
    }
}
