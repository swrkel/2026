<?php

namespace Modules\Loan\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Loan\Models\LoanApplication;
use Modules\Loan\Services\LoanApprovalWorkflowService;
use Modules\Loan\Services\LoanLocationScopeService;

class LoanApprovalQueueController extends Controller
{
    protected $locationScope;
    protected $workflow;

    public function __construct(
        LoanLocationScopeService $locationScope,
        LoanApprovalWorkflowService $workflow
    ) {
        $this->locationScope = $locationScope;
        $this->workflow = $workflow;
    }

    public function index(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $user = User::find($request->session()->get('user.id'));

        $statuses = [
            LoanApprovalWorkflowService::STATUS_DRAFT,
            LoanApprovalWorkflowService::STATUS_SUBMITTED,
            LoanApprovalWorkflowService::STATUS_UNDER_REVIEW,
            LoanApprovalWorkflowService::STATUS_APPROVED,
        ];

        $query = LoanApplication::with(['customer', 'loanProduct'])
            ->where('business_id', $businessId)
            ->whereIn('status', $statuses)
            ->latest();

        $this->locationScope->applyToQuery($query, $user, 'location_id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        $applications = $query->paginate(25);

        $locations = $this->locationScope->locationsForBusiness($businessId, $user);
        $workflowStatuses = $this->workflow->statuses();

        $summaryQuery = LoanApplication::where('business_id', $businessId);
        $this->locationScope->applyToQuery($summaryQuery, $user, 'location_id');

        $summary = [
            'draft' => (clone $summaryQuery)->where('status', LoanApprovalWorkflowService::STATUS_DRAFT)->count(),
            'submitted' => (clone $summaryQuery)->where('status', LoanApprovalWorkflowService::STATUS_SUBMITTED)->count(),
            'under_review' => (clone $summaryQuery)->where('status', LoanApprovalWorkflowService::STATUS_UNDER_REVIEW)->count(),
            'approved' => (clone $summaryQuery)->where('status', LoanApprovalWorkflowService::STATUS_APPROVED)->count(),
        ];

        return view('loan::loan_applications.approval_queue', compact(
            'applications',
            'locations',
            'workflowStatuses',
            'summary'
        ));
    }

    public function submit(Request $request, $id)
    {
        return $this->transition($request, $id, 'submit');
    }

    public function review(Request $request, $id)
    {
        return $this->transition($request, $id, 'review');
    }

    public function approve(Request $request, $id)
    {
        return $this->transition($request, $id, 'approve');
    }

    public function reject(Request $request, $id)
    {
        return $this->transition($request, $id, 'reject');
    }

    protected function transition(Request $request, $id, string $action)
    {
        try {
            $businessId = (int) $request->session()->get('user.business_id');
            $user = User::findOrFail($request->session()->get('user.id'));

            $query = LoanApplication::where('business_id', $businessId);
            $this->locationScope->applyToQuery($query, $user, 'location_id');
            $application = $query->findOrFail($id);

            switch ($action) {
                case 'submit':
                    $this->workflow->submit($application, $user);
                    $message = 'Loan application submitted successfully.';
                    break;

                case 'review':
                    $this->workflow->markUnderReview($application, $user);
                    $message = 'Loan application marked as under review.';
                    break;

                case 'approve':
                    $this->workflow->approve($application, $user);
                    $message = 'Loan application approved successfully.';
                    break;

                case 'reject':
                    $this->workflow->reject($application, $user, $request->input('reason'));
                    $message = 'Loan application rejected successfully.';
                    break;

                default:
                    throw new \InvalidArgumentException('Unsupported workflow action.');
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            \Log::error($e);

            return redirect()->back()->withErrors($e->getMessage());
        }
    }
}
