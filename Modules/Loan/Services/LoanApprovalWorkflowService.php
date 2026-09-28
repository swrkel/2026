<?php

namespace Modules\Loan\Services;

use App\User;
use Illuminate\Support\Facades\DB;
use Modules\Loan\Models\LoanApplication;
use Modules\Loan\Models\LoanAuditLog;
use Modules\Loan\Models\LoanNotification;

class LoanApprovalWorkflowService
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_DISBURSED = 'disbursed';
    public const STATUS_CANCELLED = 'cancelled';

    public function statuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_DISBURSED => 'Disbursed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public function submit(LoanApplication $application, User $user): LoanApplication
    {
        return $this->transition(
            $application,
            $user,
            self::STATUS_SUBMITTED,
            'application_submitted',
            'application_submitted',
            'Loan application submitted for review'
        );
    }

    public function markUnderReview(LoanApplication $application, User $user): LoanApplication
    {
        return $this->transition(
            $application,
            $user,
            self::STATUS_UNDER_REVIEW,
            'under_review',
            'application_under_review',
            'Loan application marked as under review'
        );
    }

    public function approve(LoanApplication $application, User $user): LoanApplication
    {
        if ((int) $application->created_by === (int) $user->id) {
            throw new \RuntimeException('Maker cannot approve own application.');
        }

        DB::transaction(function () use ($application, $user) {
            $application->status = self::STATUS_APPROVED;
            $application->workflow_stage = 'credit_approved';
            $application->approved_by = $user->id;
            $application->approved_at = now();
            $application->updated_by = $user->id;
            $application->save();

            $this->audit($application, $user, 'application_approved', 'Loan application approved');
            $this->notification($application, 'loan_approved', 'Your loan application has been approved.');
        });

        return $application->fresh();
    }

    public function reject(LoanApplication $application, User $user, ?string $reason = null): LoanApplication
    {
        return $this->transition(
            $application,
            $user,
            self::STATUS_REJECTED,
            'application_rejected',
            'application_rejected',
            $reason ?: 'Loan application rejected'
        );
    }

    public function cancel(LoanApplication $application, User $user, ?string $reason = null): LoanApplication
    {
        return $this->transition(
            $application,
            $user,
            self::STATUS_CANCELLED,
            'application_cancelled',
            'application_cancelled',
            $reason ?: 'Loan application cancelled'
        );
    }

    protected function transition(
        LoanApplication $application,
        User $user,
        string $status,
        string $workflowStage,
        string $auditType,
        string $description
    ): LoanApplication {
        DB::transaction(function () use ($application, $user, $status, $workflowStage, $auditType, $description) {
            $application->status = $status;
            $application->workflow_stage = $workflowStage;
            $application->updated_by = $user->id;

            if ($status === self::STATUS_REJECTED) {
                $application->rejected_by = $user->id;
                $application->rejected_at = now();
            }

            $application->save();
            $this->audit($application, $user, $auditType, $description);
        });

        return $application->fresh();
    }

    protected function audit(LoanApplication $application, User $user, string $type, string $description): void
    {
        LoanAuditLog::create([
            'business_id' => $application->business_id,
            'loan_application_id' => $application->id,
            'action_type' => $type,
            'description' => $description,
            'performed_by' => $user->id,
        ]);
    }

    protected function notification(LoanApplication $application, string $type, string $message): void
    {
        LoanNotification::create([
            'business_id' => $application->business_id,
            'loan_application_id' => $application->id,
            'customer_id' => $application->loan_customer_id ?? $application->customer_id,
            'notification_type' => $type,
            'channel' => 'sms',
            'message' => $message,
            'status' => 'pending',
        ]);
    }
}
