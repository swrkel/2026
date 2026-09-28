<?php

namespace Modules\Loan\Http\Controllers;

use App\BusinessLocation;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Loan\Models\Loan;
use Modules\Loan\Models\LoanApplication;
use Modules\Loan\Models\LoanDisbursement;
use Modules\Loan\Models\LoanAuditLog;
use Modules\Loan\Services\LoanScheduleService;

class LoanDisbursementController extends Controller
{
    private function businessId()
    {
        return request()->session()->get('user.business_id') ?: request()->session()->get('business.id');
    }

    private function userId()
    {
        return auth()->id() ?: request()->session()->get('user.id');
    }

    private function allowedLocationIds()
    {
        $user = User::find($this->userId());
        if (!$user || empty($user->location_permissions)) {
            return [];
        }
        $decoded = json_decode($user->location_permissions, true);
        return is_array($decoded) ? array_filter($decoded) : [];
    }

    private function applyLocationAccess($query, $column = 'location_id')
    {
        $allowed = $this->allowedLocationIds();
        if (!empty($allowed)) {
            $query->whereIn($column, $allowed);
        }
        return $query;
    }

    public function index()
    {
        $business_id = $this->businessId();
        $readyApplications = LoanApplication::with(['customer', 'loanProduct'])
            ->where('business_id', $business_id)
            ->whereIn('status', ['approved', 'ready_for_disbursement'])
            ->when(true, function ($query) { return $this->applyLocationAccess($query, 'location_id'); })
            ->latest()
            ->paginate(10, ['*'], 'ready_page');

        $disbursements = collect();
        if (Schema::hasTable('loan_disbursements')) {
            $disbursements = LoanDisbursement::with(['application.customer', 'loan'])
                ->where('business_id', $business_id)
                ->when(true, function ($query) { return $this->applyLocationAccess($query, 'location_id'); })
                ->latest()
                ->paginate(20, ['*'], 'disbursement_page');
        }

        return view('loan::disbursements.index', compact('readyApplications', 'disbursements'));
    }

    public function create($application_id)
    {
        $business_id = $this->businessId();
        $application = LoanApplication::with(['customer', 'loanProduct'])
            ->where('business_id', $business_id)
            ->when(true, function ($query) { return $this->applyLocationAccess($query, 'location_id'); })
            ->findOrFail($application_id);

        if (!in_array($application->status, ['approved', 'ready_for_disbursement'])) {
            return redirect()->route('loan.disbursements.index')->withErrors('Only approved applications can be disbursed.');
        }

        $locations = BusinessLocation::where('business_id', $business_id)->where('is_active', 1)->orderBy('name')->pluck('name', 'id');
        $defaultLocationId = $application->location_id;
        if (empty($defaultLocationId)) {
            $allowed = $this->allowedLocationIds();
            $defaultLocationId = !empty($allowed) ? reset($allowed) : $locations->keys()->first();
        }
        return view('loan::disbursements.create', compact('application', 'locations', 'defaultLocationId'));
    }

    public function store(Request $request, $application_id)
    {
        $business_id = $this->businessId();
        $user_id = $this->userId();

        $request->validate([
            'location_id' => 'required|integer',
            'disbursement_date' => 'required|date',
            'disbursement_amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:50',
            'reference_no' => 'nullable|string|max:191',
            'notes' => 'nullable|string',
        ]);

        $application = LoanApplication::with(['customer', 'loanProduct'])
            ->where('business_id', $business_id)
            ->when(true, function ($query) { return $this->applyLocationAccess($query, 'location_id'); })
            ->findOrFail($application_id);

        if (!in_array($application->status, ['approved', 'ready_for_disbursement'])) {
            return redirect()->back()->withErrors('Only approved applications can be disbursed.');
        }

        DB::beginTransaction();
        try {
            $loan = $this->createLoanFromApplication($application, $request, $business_id, $user_id);

            if (Schema::hasTable('loan_disbursements')) {
                LoanDisbursement::create([
                    'business_id' => $business_id,
                    'location_id' => $request->location_id,
                    'loan_application_id' => $application->id,
                    'loan_id' => $loan->id,
                    'loan_customer_id' => $application->loan_customer_id ?? $application->customer_id,
                    'loan_product_id' => $application->loan_product_id,
                    'approved_amount' => $this->applicationAmount($application),
                    'disbursement_amount' => $request->disbursement_amount,
                    'disbursement_date' => $request->disbursement_date,
                    'payment_method' => $request->payment_method,
                    'reference_no' => $request->reference_no,
                    'notes' => $request->notes,
                    'status' => 'disbursed',
                    'created_by' => $user_id,
                    'disbursed_by' => $user_id,
                    'disbursed_at' => now(),
                ]);
            }

            $application->status = 'disbursed';
            $application->workflow_stage = 'loan_disbursed';
            $application->location_id = $request->location_id;
            $application->disbursed_by = $user_id;
            $application->disbursed_at = now();
            $application->updated_by = $user_id;
            $application->save();

            if (class_exists(LoanScheduleService::class)) {
                try { (new LoanScheduleService())->generateSchedules($loan); } catch (\Throwable $ignored) {}
            }

            if (class_exists(LoanAuditLog::class) && Schema::hasTable('loan_audit_logs')) {
                LoanAuditLog::create([
                    'business_id' => $business_id,
                    'loan_id' => $loan->id,
                    'loan_application_id' => $application->id,
                    'action_type' => 'loan_disbursed',
                    'description' => 'Loan disbursed from Loan Disbursement screen',
                    'performed_by' => $user_id,
                ]);
            }

            DB::commit();
            return redirect()->route('loan.disbursements.index')->with('success', 'Loan disbursed successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Loan disbursement failed', ['error' => $e->getMessage()]);
            return redirect()->back()->withInput()->withErrors($e->getMessage());
        }
    }

    public function show($id)
    {
        $business_id = $this->businessId();
        $disbursement = LoanDisbursement::with(['application.customer', 'application.loanProduct', 'loan'])
            ->where('business_id', $business_id)
            ->when(true, function ($query) { return $this->applyLocationAccess($query, 'location_id'); })
            ->findOrFail($id);
        return view('loan::disbursements.show', compact('disbursement'));
    }

    private function createLoanFromApplication(LoanApplication $application, Request $request, $business_id, $user_id)
    {
        $loanNo = $this->nextLoanNo($business_id);
        $amount = (float) $request->disbursement_amount;
        $interestRate = (float) ($application->interest_rate ?? optional($application->loanProduct)->interest_rate ?? 0);
        return Loan::create([
            'business_id' => $business_id,
            'location_id' => $request->location_id,
            'loan_application_id' => $application->id,
            'loan_no' => $loanNo,
            'customer_id' => $application->loan_customer_id ?? $application->customer_id,
            'loan_customer_id' => $application->loan_customer_id ?? $application->customer_id,
            'loan_product_id' => $application->loan_product_id,
            'principal_amount' => $amount,
            'approved_amount' => $this->applicationAmount($application),
            'principal_paid' => 0,
            'interest_paid' => 0,
            'penalty_paid' => 0,
            'principal_outstanding' => $amount,
            'interest_outstanding' => ($amount * $interestRate / 100),
            'penalty_outstanding' => 0,
            'interest_rate' => $interestRate,
            'interest_type' => $application->interest_type ?? optional($application->loanProduct)->interest_type,
            'tenure' => $application->tenure ?? $application->requested_tenure,
            'tenure_type' => $application->tenure_type ?? 'months',
            'installment_frequency' => $application->installment_frequency ?? 'monthly',
            'risk_level' => $application->risk_level ?? 'low',
            'disbursement_date' => $request->disbursement_date,
            'status' => 'active',
            'created_by' => $user_id,
            'disbursed_by' => $user_id,
            'disbursed_at' => now(),
        ]);
    }

    private function nextLoanNo($business_id)
    {
        $count = Loan::where('business_id', $business_id)->count() + 1;
        $loanNo = 'LN-' . str_pad($count, 5, '0', STR_PAD_LEFT);
        while (Loan::where('business_id', $business_id)->where('loan_no', $loanNo)->exists()) {
            $count++;
            $loanNo = 'LN-' . str_pad($count, 5, '0', STR_PAD_LEFT);
        }
        return $loanNo;
    }

    private function applicationAmount(LoanApplication $application)
    {
        return (float) ($application->approved_amount ?? $application->principal_amount ?? $application->requested_amount ?? $application->amount ?? 0);
    }
}
