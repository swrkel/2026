<?php

namespace Modules\HRManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrEmployee;
use Modules\HRManager\Models\HrLeaveType;
use Modules\HRManager\Models\HrLeaveRequest;
use Modules\HRManager\Services\HrLeaveCentralService;

class HrLeaveCentralController extends Controller
{
    protected HrLeaveCentralService $leaveService;

    public function __construct(HrLeaveCentralService $leaveService)
    {
        $this->leaveService = $leaveService;
    }

    public function index(Request $request)
    {
        $businessId = session('business.id');

        $employees = $this->safeRows('hr_employees', $businessId, 200);
        $types = $this->safeRows('hr_leave_types', $businessId, 100);
        $requests = $this->leaveRequests($request, $businessId);
        $balances = $this->safeRows('hr_leave_entitlements', $businessId, 25);
        $approvals = $this->safeRows('hr_leave_request_approvals', $businessId, 25);
        $calendar = $this->safeRows('hr_leave_calendar_days', $businessId, 25);

        $stats = [
            'employees' => $this->safeCount('hr_employees', $businessId),
            'leave_types' => $this->safeCount('hr_leave_types', $businessId),
            'requests' => $this->safeCount('hr_leave_requests', $businessId),
            'pending' => $this->safeCount('hr_leave_requests', $businessId, ['status' => 'pending']),
            'approved' => $this->safeCount('hr_leave_requests', $businessId, ['status' => 'approved']),
            'rejected' => $this->safeCount('hr_leave_requests', $businessId, ['status' => 'rejected']),
            'calendar_days' => $this->safeCount('hr_leave_calendar_days', $businessId),
        ];

        return view('hrmanager::leave.index', compact('employees', 'types', 'requests', 'balances', 'approvals', 'calendar', 'stats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|integer',
            'leave_type_id' => 'required|integer',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        $this->leaveService->createRequest([
            'business_id' => session('business.id'),
            'employee_id' => $request->employee_id,
            'leave_type_id' => $request->leave_type_id,
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
            'reason' => $request->reason,
            'user_id' => auth()->id(),
        ]);

        return back()->with('status', ['success' => 1, 'msg' => 'Leave request created successfully.']);
    }

    public function approve($id)
    {
        $request = HrLeaveRequest::where('business_id', session('business.id'))->findOrFail($id);
        $this->leaveService->approve($request, auth()->id());

        return back()->with('status', ['success' => 1, 'msg' => 'Leave request approved.']);
    }

    public function reject(Request $request, $id)
    {
        $leaveRequest = HrLeaveRequest::where('business_id', session('business.id'))->findOrFail($id);
        $this->leaveService->reject($leaveRequest, $request->reason ?? 'Rejected', auth()->id());

        return back()->with('status', ['success' => 1, 'msg' => 'Leave request rejected.']);
    }

    private function hasTable(string $table): bool
    {
        try { return DB::getSchemaBuilder()->hasTable($table); } catch (\Throwable $e) { return false; }
    }

    private function safeRows(string $table, $businessId, int $limit)
    {
        if (!$this->hasTable($table)) return collect();

        return DB::table($table)
            ->where('business_id', $businessId)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    private function safeCount(string $table, $businessId, array $where = []): int
    {
        if (!$this->hasTable($table)) return 0;

        $q = DB::table($table)->where('business_id', $businessId);

        foreach ($where as $key => $value) {
            $q->where($key, $value);
        }

        return $q->count();
    }

    private function leaveRequests(Request $request, $businessId)
    {
        if (!$this->hasTable('hr_leave_requests')) return collect();

        $q = DB::table('hr_leave_requests')->where('business_id', $businessId);

        if ($request->search) {
            $q->where(function ($qq) use ($request) {
                $qq->where('request_no', 'like', '%' . $request->search . '%')
                    ->orWhere('employee_id', 'like', '%' . $request->search . '%')
                    ->orWhere('status', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->date_from) {
            $q->whereDate('from_date', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $q->whereDate('to_date', '<=', $request->date_to);
        }

        return $q->orderByDesc('id')->paginate(request('per_page') === 'all' ? 1000 : (int) request('per_page', 25));
    }
}
