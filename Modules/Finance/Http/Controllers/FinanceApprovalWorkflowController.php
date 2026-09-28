<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Entities\BusinessLocation;
use Modules\Finance\Entities\FinanceApprovalWorkflow;
use Modules\Finance\Entities\FinanceApprovalWorkflowLevel;
use Modules\Finance\Services\FinanceAuditService;

class FinanceApprovalWorkflowController extends Controller
{
    public function index(Request $request)
    {
        $business_id = session()->get('user.business_id');

        $locations = BusinessLocation::where('business_id', $business_id)
            ->pluck('name', 'location_id');

        $query = FinanceApprovalWorkflow::where('business_id', $business_id)
            ->with(['location', 'levels'])
            ->latest();

        if (!empty($request->location_id) && $request->location_id != 'all') {
            $query->where('location_id', $request->location_id);
        }

        if (!empty($request->module)) {
            $query->where('module', $request->module);
        }

        if (!empty($request->status)) {
            $query->where('status', $request->status);
        }

        $workflows = $query->paginate(25);

        return view('finance::approvals.index')
            ->with(compact(
                'workflows',
                'locations'
            ));
    }

    public function create()
    {
        $business_id = session()->get('user.business_id');

        $locations = BusinessLocation::where('business_id', $business_id)
            ->pluck('name', 'location_id');

        return view('finance::approvals.create')
            ->with(compact('locations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'workflow_name' => 'required|string|max:191',
            'module' => 'required|string|max:191',
            'approval_type' => 'required|string|max:191',
            'approval_levels' => 'required|integer|min:1|max:5',
        ]);

        DB::beginTransaction();

        try {
            $business_id = session()->get('user.business_id');

            $workflow = FinanceApprovalWorkflow::create([
                'business_id' => $business_id,
                'location_id' => $request->location_id,
                'workflow_name' => $request->workflow_name,
                'module' => $request->module,
                'approval_type' => $request->approval_type,
                'minimum_amount' => $request->minimum_amount ?? 0,
                'maximum_amount' => $request->maximum_amount ?? 0,
                'approval_levels' => $request->approval_levels,
                'status' => $request->status ?? 'active',
                'created_by' => auth()->id(),
            ]);

            for ($i = 1; $i <= $request->approval_levels; $i++) {
                FinanceApprovalWorkflowLevel::create([
                    'workflow_id' => $workflow->id,
                    'level_no' => $i,
                    'role_name' => $request->input('role_name_' . $i),
                    'approval_limit' => $request->input('approval_limit_' . $i, 0),
                    'escalation_hours' => $request->input('escalation_hours_' . $i, 24),
                    'status' => 'active',
                ]);
            }

            FinanceAuditService::log(
                'Finance Approval Workflow',
                'Created',
                'Approval workflow created: ' . $workflow->workflow_name,
                'finance_approval_workflows',
                $workflow->id,
                null,
                $workflow->toArray(),
                $workflow->location_id
            );

FinanceAuditService::log(
    'Approval Workflow',
    'Create',
    'Created finance approval workflow: ' . $workflow->workflow_name,
    'finance_approval_workflows',
    $workflow->id,
    null,
    $workflow->toArray(),
    $workflow->location_id
);

            DB::commit();

            return redirect()
                ->route('finance.approvals.index')
                ->with('status', [
                    'success' => 1,
                    'msg' => 'Approval workflow created successfully'
                ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('status', [
                'success' => 0,
                'msg' => $e->getMessage()
            ]);
        }
    }
}