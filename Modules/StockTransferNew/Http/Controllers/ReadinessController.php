<?php

namespace Modules\StockTransferNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Services\ReadinessService;

class ReadinessController extends Controller
{
    protected ReadinessService $readinessService;

    public function __construct(ReadinessService $readinessService)
    {
        $this->readinessService = $readinessService;
    }

    public function index()
    {
        $summary = $this->readinessService->summary();
        $checks = $this->readinessService->checks();

        return view('stocktransfernew::readiness.index', compact('summary', 'checks'));
    }

    public function sqlChecklist()
    {
        $files = $this->readinessService->sqlFiles();

        return view('stocktransfernew::readiness.sql_checklist', compact('files'));
    }

    public function exportChecklist()
    {
        $items = $this->readinessService->exportChecklist();

        return view('stocktransfernew::readiness.export_checklist', compact('items'));
    }
}
