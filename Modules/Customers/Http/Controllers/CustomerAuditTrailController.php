<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Customers\Services\CustomerService;

class CustomerAuditTrailController extends Controller
{
    protected $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    public function index($id, Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        $customer = $this->customerService->customerQuery($businessId)->where('id', $id)->first();
        abort_if(empty($customer), 404);

        $auditTrailEnabled = Schema::hasTable('customer_audit_trails');
        $activityEnabled = Schema::hasTable('customer_activity_logs');
        $auditTrails = collect([]);
        $activities = collect([]);

        if ($auditTrailEnabled) {
            $auditTrails = DB::table('customer_audit_trails')
                ->leftJoin('users', 'customer_audit_trails.created_by', '=', 'users.id')
                ->where('customer_audit_trails.business_id', $businessId)
                ->where('customer_audit_trails.customer_id', $id)
                ->select('customer_audit_trails.*', 'users.first_name', 'users.last_name')
                ->orderByDesc('customer_audit_trails.id')
                ->paginate(25);
        }

        if ($activityEnabled) {
            $activities = DB::table('customer_activity_logs')
                ->leftJoin('users', 'customer_activity_logs.created_by', '=', 'users.id')
                ->where('customer_activity_logs.business_id', $businessId)
                ->where('customer_activity_logs.customer_id', $id)
                ->select('customer_activity_logs.*', 'users.first_name', 'users.last_name')
                ->orderByDesc('customer_activity_logs.id')
                ->limit(50)
                ->get();
        }

        return view('customers::audit.index')->with(compact('customer', 'auditTrails', 'activities', 'auditTrailEnabled', 'activityEnabled'));
    }
}
