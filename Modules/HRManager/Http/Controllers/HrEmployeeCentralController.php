<?php

namespace Modules\HRManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrEmployee;
use Modules\HRManager\Services\HrEmployeeCentralService;

class HrEmployeeCentralController extends Controller
{
    public function index(Request $request)
    {
        $businessId = session('business.id');
        $employees = HrEmployee::where('business_id', $businessId)
            ->when($request->search, function ($q) use ($request) {
                $q->where('full_name', 'like', '%' . $request->search . '%')
                  ->orWhere('employee_no', 'like', '%' . $request->search . '%')
                  ->orWhere('mobile', 'like', '%' . $request->search . '%');
            })
            ->orderByDesc('id')
            ->paginate(25);

        $stats = [
            'employees' => HrEmployee::where('business_id', $businessId)->count(),
            'active' => HrEmployee::where('business_id', $businessId)->where('status', 1)->count(),
            'inactive' => HrEmployee::where('business_id', $businessId)->where('status', 0)->count(),
            'probation' => HrEmployee::where('business_id', $businessId)->where('employment_status', 'probation')->count(),
        ];

        return view('hrmanager::employees.index', compact('employees', 'stats'));
    }

    public function create()
    {
        return view('hrmanager::employees.create');
    }

    public function store(Request $request, HrEmployeeCentralService $service)
    {
        $request->validate(['full_name' => 'required|string|max:255']);

        $data = $request->all();
        $data['business_id'] = session('business.id');
        $data['created_by'] = auth()->id();
        $data['employee_no'] = $data['employee_no'] ?: 'EMP-' . now()->format('YmdHis');
        $data['status'] = $data['status'] ?? 1;

        $employee = HrEmployee::create($data);
        $service->log($employee->business_id, $employee->id, 'employee_created', 'Employee central record created', null, auth()->id());

        return redirect()->route('hrmanager.employees.show', $employee->id);
    }

    public function show($id)
    {
        $businessId = session('business.id');
        $employee = HrEmployee::where('business_id', $businessId)->findOrFail($id);

        $tabs = [];
        foreach ([
            'family' => 'hr_employee_family_members',
            'education' => 'hr_employee_education',
            'experience' => 'hr_employee_experience',
            'skills' => 'hr_employee_skills',
            'banks' => 'hr_employee_bank_accounts',
            'assets' => 'hr_employee_assets',
            'notes' => 'hr_employee_notes',
            'activity' => 'hr_employee_activity_logs',
            'attendance' => 'hr_attendance',
            'leave' => 'hr_leave_requests',
            'payroll' => 'hr_payslips',
        ] as $key => $table) {
            try {
                $tabs[$key] = DB::getSchemaBuilder()->hasTable($table)
                    ? DB::table($table)->where('business_id', $businessId)->where('employee_id', $id)->orderByDesc('id')->limit(20)->get()
                    : collect();
            } catch (\Throwable $e) {
                $tabs[$key] = collect();
            }
        }

        return view('hrmanager::employees.show', compact('employee', 'tabs'));
    }
}
