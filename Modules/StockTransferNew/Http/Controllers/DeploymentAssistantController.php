<?php

namespace Modules\StockTransferNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Services\DeploymentAssistantService;

class DeploymentAssistantController extends Controller
{
    protected $service;

    public function __construct(DeploymentAssistantService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $data = $this->service->dashboard($request);
        return view('stocktransfernew::deployment_assistant.index', $data);
    }

    public function sqlTracker(Request $request)
    {
        $data = $this->service->sqlTracker($request);
        return view('stocktransfernew::deployment_assistant.sql_tracker', $data);
    }

    public function markSqlExecuted(Request $request)
    {
        $request->validate([
            'script_name' => 'required|string|max:191',
            'database_scope' => 'required|string|max:50',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $this->service->markSqlExecuted($request);

        return redirect()->back()->with('status', __('stocktransfernew::messages.sql_marked_executed'));
    }

    public function rollbackPlan(Request $request)
    {
        $data = $this->service->rollbackPlan($request);
        return view('stocktransfernew::deployment_assistant.rollback_plan', $data);
    }

    public function exportChecks(Request $request)
    {
        return $this->service->exportChecks($request);
    }
}
