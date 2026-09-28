# Loan Approval Workflow

The Loan module uses the same approval lifecycle planned for all Banking products:

1. Draft
2. Submitted
3. Under Review
4. Approved
5. Disbursed / Active
6. Rejected / Cancelled / Closed

## New Approval Queue

URL: `/loan/approval-queue`

The queue is business-aware and location-aware. It uses `business_id` from the session and respects the logged-in user's `location_permissions`.

## Services

- `Modules\Loan\Services\LoanApprovalWorkflowService`
- `Modules\Loan\Services\LoanLocationScopeService`

Controllers should call these services instead of duplicating workflow and location filtering logic.
