<?php

namespace Modules\HRManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HRManager\Models\HrPayrollPeriod;
use Modules\HRManager\Models\HrSalaryStructure;
use Modules\HRManager\Models\HrPayrollRun;
use Modules\HRManager\Models\HrPayslip;
use Modules\HRManager\Services\HrPayrollService;

class HrPayrollController extends Controller
{
    protected HrPayrollService $payrollService;

    public function __construct(HrPayrollService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    public function dashboard()
    {
        $businessId = session('business.id');

        $periodsCount = HrPayrollPeriod::where('business_id', $businessId)->count();
        $structuresCount = HrSalaryStructure::where('business_id', $businessId)->count();
        $runsCount = HrPayrollRun::where('business_id', $businessId)->count();
        $netTotal = HrPayrollRun::where('business_id', $businessId)->sum('net_total');

        $runs = HrPayrollRun::where('business_id', $businessId)->orderByDesc('id')->limit(10)->get();

        return view('hrmanager::payroll.dashboard', compact('periodsCount','structuresCount','runsCount','netTotal','runs'));
    }

    public function periods()
    {
        $periods = HrPayrollPeriod::where('business_id', session('business.id'))->orderByDesc('from_date')->paginate(25);
        return view('hrmanager::payroll.periods', compact('periods'));
    }

    public function storePeriod(Request $request)
    {
        $request->validate([
            'period_name' => 'required|string|max:150',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        HrPayrollPeriod::create([
            'business_id' => session('business.id'),
            'period_code' => $request->period_code ?: ('PER-' . now()->format('YmHis')),
            'period_name' => $request->period_name,
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
            'pay_date' => $request->pay_date,
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        return back()->with('status', ['success' => 1, 'msg' => 'Payroll period saved successfully.']);
    }

    public function salaryStructures()
    {
        $structures = HrSalaryStructure::where('business_id', session('business.id'))->orderByDesc('id')->paginate(25);
        return view('hrmanager::payroll.salary_structures', compact('structures'));
    }

    public function storeSalaryStructure(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|integer',
            'effective_from' => 'required|date',
            'basic_salary' => 'required|numeric|min:0',
        ]);

        HrSalaryStructure::create([
            'business_id' => session('business.id'),
            'employee_id' => $request->employee_id,
            'structure_no' => $request->structure_no ?: ('SAL-' . now()->format('YmdHis')),
            'effective_from' => $request->effective_from,
            'basic_salary' => $request->basic_salary,
            'salary_frequency' => $request->salary_frequency ?? 'monthly',
            'bank_name' => $request->bank_name,
            'bank_account_no' => $request->bank_account_no,
            'epf_no' => $request->epf_no,
            'etf_no' => $request->etf_no,
            'tax_no' => $request->tax_no,
            'status' => 'active',
            'created_by' => auth()->id(),
        ]);

        return back()->with('status', ['success' => 1, 'msg' => 'Salary structure saved successfully.']);
    }

    public function runs()
    {
        $businessId = session('business.id');
        $runs = HrPayrollRun::where('business_id', $businessId)->orderByDesc('id')->paginate(25);
        $periods = HrPayrollPeriod::where('business_id', $businessId)->where('status', 'draft')->orderByDesc('from_date')->get();
        return view('hrmanager::payroll.runs', compact('runs', 'periods'));
    }

    public function storeRun(Request $request)
    {
        $request->validate(['payroll_period_id' => 'required|integer']);

        $this->payrollService->createDraftRun([
            'business_id' => session('business.id'),
            'payroll_period_id' => $request->payroll_period_id,
            'remarks' => $request->remarks,
            'user_id' => auth()->id(),
        ]);

        return back()->with('status', ['success' => 1, 'msg' => 'Draft payroll run created successfully.']);
    }

    public function payslips()
    {
        $payslips = HrPayslip::where('business_id', session('business.id'))->orderByDesc('id')->paginate(25);
        return view('hrmanager::payroll.payslips', compact('payslips'));
    }
}
