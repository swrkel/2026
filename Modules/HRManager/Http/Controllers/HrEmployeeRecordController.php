<?php

namespace Modules\HRManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HRManager\Models\HrEmployeeContract;
use Modules\HRManager\Models\HrEmployeeCertification;
use Modules\HRManager\Models\HrEmployeeAppraisal;
use Modules\HRManager\Models\HrEmployeeWarning;
use Modules\HRManager\Models\HrEmployeeResignation;
use Modules\HRManager\Models\HrEmployeeTermination;
use Modules\HRManager\Services\HrEmployeeRecordService;

class HrEmployeeRecordController extends Controller
{
    protected HrEmployeeRecordService $recordService;

    public function __construct(HrEmployeeRecordService $recordService)
    {
        $this->recordService = $recordService;
    }

    public function dashboard()
    {
        $businessId = session('business.id');

        return view('hrmanager::employee_records.dashboard', [
            'contractsCount' => HrEmployeeContract::where('business_id', $businessId)->count(),
            'certificationsCount' => HrEmployeeCertification::where('business_id', $businessId)->count(),
            'appraisalsCount' => HrEmployeeAppraisal::where('business_id', $businessId)->count(),
            'warningsCount' => HrEmployeeWarning::where('business_id', $businessId)->count(),
            'contracts' => HrEmployeeContract::where('business_id', $businessId)->orderByDesc('id')->limit(10)->get(),
        ]);
    }

    public function contracts()
    {
        $contracts = HrEmployeeContract::where('business_id', session('business.id'))->orderByDesc('id')->paginate(25);
        return view('hrmanager::employee_records.contracts', compact('contracts'));
    }

    public function storeContract(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|integer',
            'start_date' => 'required|date',
        ]);

        $contract = HrEmployeeContract::create([
            'business_id' => session('business.id'),
            'employee_id' => $request->employee_id,
            'contract_no' => $request->contract_no ?: ('CON-' . now()->format('YmdHis')),
            'contract_type' => $request->contract_type ?? 'employment',
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'probation_end_date' => $request->probation_end_date,
            'basic_salary' => $request->basic_salary ?? 0,
            'terms' => $request->terms,
            'status' => 'active',
            'created_by' => auth()->id(),
        ]);

        $this->recordService->audit([
            'business_id' => session('business.id'),
            'employee_id' => $request->employee_id,
            'record_type' => 'contract',
            'record_id' => $contract->id,
            'action' => 'created',
            'new_status' => 'active',
            'user_id' => auth()->id(),
        ]);

        return back()->with('status', ['success' => 1, 'msg' => 'Contract saved successfully.']);
    }

    public function appraisals()
    {
        $appraisals = HrEmployeeAppraisal::where('business_id', session('business.id'))->orderByDesc('id')->paginate(25);
        return view('hrmanager::employee_records.appraisals', compact('appraisals'));
    }

    public function storeAppraisal(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|integer',
            'appraisal_period_from' => 'required|date',
            'appraisal_period_to' => 'required|date|after_or_equal:appraisal_period_from',
        ]);

        HrEmployeeAppraisal::create([
            'business_id' => session('business.id'),
            'employee_id' => $request->employee_id,
            'appraisal_no' => $request->appraisal_no ?: ('APR-' . now()->format('YmdHis')),
            'appraisal_period_from' => $request->appraisal_period_from,
            'appraisal_period_to' => $request->appraisal_period_to,
            'score' => $request->score,
            'rating' => $request->rating,
            'strengths' => $request->strengths,
            'improvements' => $request->improvements,
            'goals' => $request->goals,
            'recommendation' => $request->recommendation,
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        return back()->with('status', ['success' => 1, 'msg' => 'Appraisal saved successfully.']);
    }

    public function warnings()
    {
        $warnings = HrEmployeeWarning::where('business_id', session('business.id'))->orderByDesc('id')->paginate(25);
        return view('hrmanager::employee_records.warnings', compact('warnings'));
    }

    public function storeWarning(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|integer',
            'warning_date' => 'required|date',
            'subject' => 'required|string|max:180',
        ]);

        HrEmployeeWarning::create([
            'business_id' => session('business.id'),
            'employee_id' => $request->employee_id,
            'warning_no' => $request->warning_no ?: ('WRN-' . now()->format('YmdHis')),
            'warning_date' => $request->warning_date,
            'warning_type' => $request->warning_type,
            'severity' => $request->severity ?? 'normal',
            'subject' => $request->subject,
            'description' => $request->description,
            'action_required' => $request->action_required,
            'status' => 'issued',
            'issued_by' => auth()->id(),
        ]);

        return back()->with('status', ['success' => 1, 'msg' => 'Warning saved successfully.']);
    }

    public function exitRecords()
    {
        $resignations = HrEmployeeResignation::where('business_id', session('business.id'))->orderByDesc('id')->limit(20)->get();
        $terminations = HrEmployeeTermination::where('business_id', session('business.id'))->orderByDesc('id')->limit(20)->get();
        return view('hrmanager::employee_records.exit', compact('resignations', 'terminations'));
    }
}
