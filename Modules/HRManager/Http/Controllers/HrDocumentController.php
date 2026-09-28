<?php

namespace Modules\HRManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HRManager\Models\HrContract;
use Modules\HRManager\Models\HrCertificate;
use Modules\HRManager\Models\HrWarningLetter;
use Modules\HRManager\Models\HrPerformanceReview;
use Modules\HRManager\Models\HrResignation;
use Modules\HRManager\Models\HrTermination;
use Modules\HRManager\Models\HrDocumentExpiryAlert;

class HrDocumentController extends Controller
{
    public function dashboard()
    {
        $businessId = session('business.id');

        $contractCount = HrContract::where('business_id', $businessId)->count();
        $certificateCount = HrCertificate::where('business_id', $businessId)->count();
        $warningCount = HrWarningLetter::where('business_id', $businessId)->count();
        $reviewCount = HrPerformanceReview::where('business_id', $businessId)->count();
        $expiryAlerts = HrDocumentExpiryAlert::where('business_id', $businessId)->where('alert_status', 'pending')->orderBy('expiry_date')->limit(10)->get();

        return view('hrmanager::documents.dashboard', compact('contractCount','certificateCount','warningCount','reviewCount','expiryAlerts'));
    }

    public function contracts()
    {
        $contracts = HrContract::where('business_id', session('business.id'))->orderByDesc('id')->paginate(25);
        return view('hrmanager::documents.contracts', compact('contracts'));
    }

    public function storeContract(Request $request)
    {
        $request->validate(['employee_id' => 'required|integer', 'title' => 'required|string|max:180']);

        HrContract::create([
            'business_id' => session('business.id'),
            'employee_id' => $request->employee_id,
            'contract_no' => $request->contract_no ?: ('CON-' . now()->format('YmdHis')),
            'contract_type' => $request->contract_type ?? 'employment',
            'title' => $request->title,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'probation_end_date' => $request->probation_end_date,
            'salary_amount' => $request->salary_amount ?? 0,
            'status' => $request->status ?? 'draft',
            'terms' => $request->terms,
            'remarks' => $request->remarks,
            'created_by' => auth()->id(),
        ]);

        return back()->with('status', ['success' => 1, 'msg' => 'Contract saved successfully.']);
    }

    public function certificates()
    {
        $certificates = HrCertificate::where('business_id', session('business.id'))->orderByDesc('id')->paginate(25);
        return view('hrmanager::documents.certificates', compact('certificates'));
    }

    public function storeCertificate(Request $request)
    {
        $request->validate(['employee_id' => 'required|integer', 'certificate_type' => 'required|string', 'title' => 'required|string']);

        HrCertificate::create([
            'business_id' => session('business.id'),
            'employee_id' => $request->employee_id,
            'certificate_no' => $request->certificate_no ?: ('CER-' . now()->format('YmdHis')),
            'certificate_type' => $request->certificate_type,
            'title' => $request->title,
            'issued_date' => $request->issued_date,
            'expiry_date' => $request->expiry_date,
            'issuing_authority' => $request->issuing_authority,
            'verification_status' => 'pending',
            'remarks' => $request->remarks,
            'created_by' => auth()->id(),
        ]);

        return back()->with('status', ['success' => 1, 'msg' => 'Certificate saved successfully.']);
    }

    public function warnings()
    {
        $warnings = HrWarningLetter::where('business_id', session('business.id'))->orderByDesc('warning_date')->paginate(25);
        return view('hrmanager::documents.warnings', compact('warnings'));
    }

    public function storeWarning(Request $request)
    {
        $request->validate(['employee_id' => 'required|integer', 'warning_date' => 'required|date', 'subject' => 'required|string']);

        HrWarningLetter::create([
            'business_id' => session('business.id'),
            'employee_id' => $request->employee_id,
            'warning_no' => $request->warning_no ?: ('WRN-' . now()->format('YmdHis')),
            'warning_date' => $request->warning_date,
            'warning_type' => $request->warning_type,
            'severity' => $request->severity ?? 'normal',
            'subject' => $request->subject,
            'description' => $request->description,
            'action_required' => $request->action_required,
            'response_due_date' => $request->response_due_date,
            'issued_by' => auth()->id(),
        ]);

        return back()->with('status', ['success' => 1, 'msg' => 'Warning letter saved successfully.']);
    }

    public function reviews()
    {
        $reviews = HrPerformanceReview::where('business_id', session('business.id'))->orderByDesc('review_date')->paginate(25);
        return view('hrmanager::documents.reviews', compact('reviews'));
    }

    public function exits()
    {
        $resignations = HrResignation::where('business_id', session('business.id'))->orderByDesc('id')->limit(20)->get();
        $terminations = HrTermination::where('business_id', session('business.id'))->orderByDesc('id')->limit(20)->get();
        return view('hrmanager::documents.exits', compact('resignations', 'terminations'));
    }
}
