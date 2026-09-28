<?php

namespace Modules\StockTransferNew\Http\Controllers\Support;

use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Services\Support\DiagnosticsService;

class DiagnosticsController extends Controller
{
    protected DiagnosticsService $diagnosticsService;

    public function __construct(DiagnosticsService $diagnosticsService)
    {
        $this->diagnosticsService = $diagnosticsService;
    }

    public function index()
    {
        $summary = $this->diagnosticsService->summary();
        $checks = $this->diagnosticsService->checks();

        return view('stocktransfernew::support.diagnostics', compact('summary', 'checks'));
    }

    public function tenantScope()
    {
        $items = $this->diagnosticsService->tenantScopeChecks();

        return view('stocktransfernew::support.tenant_scope', compact('items'));
    }

    public function permissions()
    {
        $items = $this->diagnosticsService->permissionChecks();

        return view('stocktransfernew::support.permissions', compact('items'));
    }

    public function routesAssets()
    {
        $items = $this->diagnosticsService->routesAndAssets();

        return view('stocktransfernew::support.routes_assets', compact('items'));
    }
}
