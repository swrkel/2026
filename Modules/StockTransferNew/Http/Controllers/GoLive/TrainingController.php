<?php

namespace Modules\StockTransferNew\Http\Controllers\GoLive;

use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Services\GoLive\TrainingGuideService;

class TrainingController extends Controller
{
    public function index(TrainingGuideService $service)
    {
        $sections = $service->sections();
        return view('stocktransfernew::go_live.training', compact('sections'));
    }
}
