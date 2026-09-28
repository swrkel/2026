<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finance\Entities\BusinessLocation;
use Modules\Finance\Entities\User;
use Modules\Finance\Entities\FinanceEscalation;
use Modules\Finance\Services\FinanceAuditService;
use Modules\Finance\Services\FinanceNotificationService;

class FinanceEscalationController extends Controller
{
    public function index(Request $request)
    {
        $business_id = session()->get('user.business_id');

        $locations = BusinessLocation::where('business_id', $business_id)
            ->pluck('name', 'location_id');

        $query = FinanceEscalation::where('business_id', $business_id)
            ->with(['location', 'assignedUser', 'createdBy'])
            ->latest();

        if (!empty($request->location_id) && $request->location_id != 'all') {
            $query->where('location_id', $request->location_id);
        }

        if (!empty($request->module)) {
            $query->where('module', $request->module);
        }

        if (!empty($request->severity)) {
            $query->where('severity', $request->severity);
        }

        if (!empty($request->status)) {
            $query->where('status', $request->status);
        }

        $escalations = $query->paginate(25);

        return view('finance::escalations.index')
            ->with(compact(
                'escalations',
                'locations'
            ));
    }

    public function create()
    {
        $business_id = session()->get('user.business_id');

        $locations = BusinessLocation::where('business_id', $business_id)
            ->pluck('name', 'location_id');

        $users = User::pluck('username', 'id');

        return view('finance::escalations.create')
            ->with(compact(
                'locations',
                'users'
            ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'module' => 'required|string|max:191',
            'escalation_type' => 'required|string|max:191',
            'severity' => 'required|string|max:50',
            'subject' => 'required|string|max:191',
        ]);

        $business_id = session()->get('user.business_id');

        $escalation = FinanceEscalation::create([
            'business_id' => $business_id,
            'location_id' => $request->location_id,
            'escalation_no' => 'FESC-' . time(),
            'module' => $request->module,
            'escalation_type' => $request->escalation_type,
            'severity' => $request->severity,
            'subject' => $request->subject,
            'description' => $request->description,
            'reference_type' => $request->reference_type,
            'reference_id' => $request->reference_id,
            'assigned_to' => $request->assigned_to,
            'status' => 'open',
            'created_by' => auth()->id(),
        ]);

        FinanceAuditService::log(
            'Finance Escalation',
            'Created',
            'Finance escalation created: ' . $escalation->subject,
            'finance_escalations',
            $escalation->id,
            null,
            $escalation->toArray(),
            $escalation->location_id
        );

FinanceNotificationService::create(
    'Finance Escalation',
    'New Finance Escalation Created',
    'Escalation: ' . $escalation->subject,
    $escalation->assigned_to,
    $escalation->severity,
    'finance_escalations',
    $escalation->id,
    $escalation->location_id
);

        return redirect()
            ->route('finance.escalations.index')
            ->with('status', [
                'success' => 1,
                'msg' => 'Finance escalation created successfully'
            ]);
    }
}