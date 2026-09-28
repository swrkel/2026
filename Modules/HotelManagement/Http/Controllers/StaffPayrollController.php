<?php

namespace Modules\HotelManagement\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\HotelManagement\Services\StaffPayrollService;

class StaffPayrollController extends Controller
{
    public function __construct(protected StaffPayrollService $service) {}

    public function index()
    {
        return view('hotelmanagement::staff_payroll.index', [
            'staffPayroll' => $this->service->dashboard(),
        ]);
    }

    public function rule(Request $request)
    {
        $this->service->rule($request->validate([
            'staff_id' => 'required|integer',
            'salary_type' => 'required|string|max:40',
            'basic_salary' => 'required|numeric|min:0',
            'ot_rate' => 'nullable|numeric|min:0',
            'allowance_amount' => 'nullable|numeric|min:0',
            'deduction_amount' => 'nullable|numeric|min:0',
            'effective_from' => 'required|date',
            'status' => 'nullable|string|max:40',
            'remarks' => 'nullable|string|max:1000',
        ]), optional($request->user())->id);
        return back()->with('status', 'Payroll rule saved successfully.');
    }

    public function run(Request $request)
    {
        $this->service->run($request->validate([
            'payroll_no' => 'nullable|string|max:60',
            'period_from' => 'required|date',
            'period_to' => 'required|date|after_or_equal:period_from',
            'pay_date' => 'nullable|date',
            'department' => 'nullable|string|max:100',
            'remarks' => 'nullable|string|max:1000',
        ]), optional($request->user())->id);
        return back()->with('status', 'Payroll run generated successfully.');
    }

    public function lineAdjust($id, Request $request)
    {
        $this->service->lineAdjust((int) $id, $request->validate([
            'ot_hours' => 'nullable|numeric|min:0',
            'allowance_amount' => 'nullable|numeric|min:0',
            'deduction_amount' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:1000',
        ]), optional($request->user())->id);
        return back()->with('status', 'Payroll line adjusted successfully.');
    }

    public function status($id, Request $request)
    {
        $this->service->status((int) $id, $request->validate([
            'status' => 'required|string|max:40',
            'remarks' => 'nullable|string|max:1000',
        ]), optional($request->user())->id);
        return back()->with('status', 'Payroll run status updated successfully.');
    }
}
