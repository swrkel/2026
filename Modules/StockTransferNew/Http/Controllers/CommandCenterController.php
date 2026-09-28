<?php

namespace Modules\StockTransferNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Services\StockTransferCommandCenterService;
use Modules\StockTransferNew\Utilities\StockTransferTenant;

class CommandCenterController extends Controller
{
    public function __construct(protected StockTransferCommandCenterService $commandCenter) {}

    public function index()
    {
        $data = $this->commandCenter->dashboard((int) StockTransferTenant::businessId());
        return view('stocktransfernew::command_center.index', $data);
    }

    public function queue(Request $request)
    {
        $transfers = $this->commandCenter->operationalQueue($request->all());
        return view('stocktransfernew::command_center.queue', compact('transfers'));
    }
}
