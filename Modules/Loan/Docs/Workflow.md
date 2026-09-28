# Loan Approval Workflow

The Loan module uses one consistent workflow pattern:

Draft -> Submitted -> Under Review -> Approved -> Disbursed/Active -> Settled/Closed

The shared status labels and allowed application transitions are defined in:

`Modules/Loan/Config/workflow.php`

The shared workflow service is:

`Modules/Loan/Services/LoanApprovalWorkflowService.php`

Controllers should use this service for approval/rejection/disbursement logic instead of duplicating status updates.
