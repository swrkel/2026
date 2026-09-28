<?php
namespace Modules\AirlineTicketingNew\Http\Controllers\Workflow;

use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\WorkflowInstance;
use Modules\AirlineTicketingNew\Services\Workflow\WorkflowEngine;

class WorkflowController extends Controller
{
    public function index()
    {
        $records = WorkflowInstance::query()
            ->where('business_id', (int) session('business.id'))
            ->latest('id')
            ->paginate(50);

        return view('airlineticketingnew::workflow.index', compact('records'));
    }

    public function approve(WorkflowInstance $workflow, WorkflowEngine $engine)
    {
        abort_unless((int) $workflow->business_id === (int) session('business.id'), 404);
        $engine->approve($workflow, request('comment'));

        return back()->with('status', ['success' => 1, 'msg' => 'Workflow approved successfully.']);
    }
}
