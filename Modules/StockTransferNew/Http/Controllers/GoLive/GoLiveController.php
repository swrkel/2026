<?php

namespace Modules\StockTransferNew\Http\Controllers\GoLive;

use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Services\GoLive\GoLiveReadinessService;

class GoLiveController extends Controller
{
    protected GoLiveReadinessService $service;

    public function __construct(GoLiveReadinessService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $readiness = $this->service->summary();
        return view('stocktransfernew::go_live.index', compact('readiness'));
    }
}
