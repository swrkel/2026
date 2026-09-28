<?php

namespace Modules\StockTransferNew\Http\Controllers\TesterSupport;

use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Services\TesterSupport\TroubleshootingService;

class TroubleshootingController extends Controller
{
    protected TroubleshootingService $troubleshootingService;

    public function __construct(TroubleshootingService $troubleshootingService)
    {
        $this->troubleshootingService = $troubleshootingService;
    }

    public function index()
    {
        $items = $this->troubleshootingService->items();
        return view('stocktransfernew::tester_support.troubleshooting', compact('items'));
    }
}
