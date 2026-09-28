<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finance\Entities\FinanceAuditLog;
use Modules\Finance\Entities\BusinessLocation;

class FinanceAuditController extends Controller
{
    public function index(Request $request)
    {
        $business_id = session()->get('user.business_id');

        $locations = BusinessLocation::where('business_id', $business_id)
            ->pluck('name', 'location_id');

        $query = FinanceAuditLog::where('business_id', $business_id)
            ->with(['user', 'location'])
            ->latest();

        if (!empty($request->location_id) && $request->location_id != 'all') {
            $query->where('location_id', $request->location_id);
        }

        if (!empty($request->module)) {
            $query->where('module', $request->module);
        }

        if (!empty($request->action)) {
            $query->where('action', $request->action);
        }

        if (!empty($request->from_date)) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if (!empty($request->to_date)) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $audit_logs = $query->paginate(25);

        $modules = FinanceAuditLog::where('business_id', $business_id)
            ->select('module')
            ->distinct()
            ->pluck('module', 'module');

        $actions = FinanceAuditLog::where('business_id', $business_id)
            ->select('action')
            ->distinct()
            ->pluck('action', 'action');

        return view('finance::audit.index')
            ->with(compact(
                'audit_logs',
                'locations',
                'modules',
                'actions'
            ));
    }
}