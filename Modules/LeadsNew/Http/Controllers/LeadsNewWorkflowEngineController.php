<?php

namespace Modules\LeadsNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\LeadsNew\Services\LeadsNewWorkflowEngineService;

class LeadsNewWorkflowEngineController extends Controller
{
    public function kanban(LeadsNewWorkflowEngineService $service)
    {
        $businessId = request()->session()->get('user.business_id');
        return view('leadsnew::workflow.kanban', ['stages' => $service->stages($businessId)]);
    }

    public function move(Request $request, LeadsNewWorkflowEngineService $service)
    {
        $request->validate(['lead_id' => 'required|integer', 'status_id' => 'required|integer']);
        $service->move((int) $request->lead_id, (int) $request->status_id, (int) auth()->id());
        return response()->json(['success' => true, 'msg' => __('leadsnew::lang.status_updated')]);
    }
}
