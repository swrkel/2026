<?php

namespace Modules\HRManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use Modules\HRManager\Models\HrEmployee;
use Modules\HRManager\Services\HrEmployeeService;

class HrEmployeeController extends Controller
{
    protected HrEmployeeService $employees;

    public function __construct(HrEmployeeService $employees)
    {
        $this->employees = $employees;
    }

    public function index(Request $request)
    {
        $employees = $this->employees->list($request->all());
        return view('hrmanager::employees.index', compact('employees'));
    }

    public function create()
    {
        $employee = new HrEmployee(['employee_status' => 'active', 'employment_type' => 'full_time']);
        return view('hrmanager::employees.form', compact('employee'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $employee = $this->employees->create($data, optional($request->user())->id);
        return redirect()->route('hr.employees.show', $employee)->with('status', 'Employee created successfully.');
    }

    public function show(HrEmployee $employee)
    {
        $documents = \Modules\HRManager\Models\HrEmployeeDocument::where('employee_id', $employee->id)->latest()->get();
        $emergency = \Modules\HRManager\Models\HrEmployeeEmergencyContact::where('employee_id', $employee->id)->first();
        return view('hrmanager::employees.show', compact('employee', 'documents', 'emergency'));
    }

    public function edit(HrEmployee $employee)
    {
        $emergency = \Modules\HRManager\Models\HrEmployeeEmergencyContact::where('employee_id', $employee->id)->first();
        return view('hrmanager::employees.form', compact('employee', 'emergency'));
    }

    public function update(Request $request, HrEmployee $employee)
    {
        $data = $this->validated($request, $employee->id);
        $employee = $this->employees->update($employee, $data, optional($request->user())->id);
        return redirect()->route('hr.employees.show', $employee)->with('status', 'Employee updated successfully.');
    }

    public function destroy(HrEmployee $employee)
    {
        $this->employees->delete($employee);
        return redirect()->route('hr.employees.index')->with('status', 'Employee deleted successfully.');
    }

    public function exportCsv(Request $request)
    {
        $rows = HrEmployee::query()->search($request->get('search'))->orderBy('employee_code')->get();
        $csv = "Code,Name,NIC,Mobile,Email,Status,Joining Date\n";
        foreach ($rows as $r) {
            $csv .= sprintf('"%s","%s","%s","%s","%s","%s","%s"' . "\n", $r->employee_code, $r->display_name, $r->nic_no, $r->mobile, $r->email, $r->employee_status, optional($r->joining_date)->format('Y-m-d'));
        }
        return Response::make($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="hr-employees.csv"']);
    }

    protected function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'employee_code' => 'required|max:50|unique:hr_employees,employee_code,' . ($ignoreId ?: 'NULL') . ',id,deleted_at,NULL',
            'first_name' => 'required|max:100', 'last_name' => 'nullable|max:100', 'display_name' => 'nullable|max:150',
            'nic_no' => 'nullable|max:50', 'passport_no' => 'nullable|max:50', 'gender' => 'nullable|max:20', 'date_of_birth' => 'nullable|date',
            'mobile' => 'nullable|max:30', 'phone' => 'nullable|max:30', 'email' => 'nullable|email|max:150',
            'address_line_1' => 'nullable|max:255', 'address_line_2' => 'nullable|max:255', 'city' => 'nullable|max:100', 'state' => 'nullable|max:100', 'country' => 'nullable|max:100', 'postal_code' => 'nullable|max:30',
            'department_id' => 'nullable|integer', 'designation_id' => 'nullable|integer', 'branch_id' => 'nullable|integer', 'shift_id' => 'nullable|integer',
            'joining_date' => 'nullable|date', 'employment_type' => 'required|max:50', 'employee_status' => 'required|max:50',
            'basic_salary' => 'nullable|numeric|min:0', 'bank_name' => 'nullable|max:150', 'bank_branch' => 'nullable|max:150', 'bank_account_no' => 'nullable|max:100', 'epf_no' => 'nullable|max:100', 'etf_no' => 'nullable|max:100', 'notes' => 'nullable|max:2000',
            'emergency.contact_name' => 'nullable|max:150', 'emergency.relationship' => 'nullable|max:100', 'emergency.mobile' => 'nullable|max:30', 'emergency.phone' => 'nullable|max:30', 'emergency.address' => 'nullable|max:255', 'emergency.notes' => 'nullable|max:1000',
        ]);
    }
}
