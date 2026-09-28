# Loan Module Completion Checklist

This checklist is kept inside the Loan module for future maintenance.

## Core Areas
- Dashboard loads without route/namespace errors.
- Loan product master works.
- Loan application lifecycle works.
- Approval workflow follows standard statuses: draft, submitted, under_review, approved, active, closed.
- Business and location filters use existing ERP business and business_locations tables.
- Reports remain inside Modules/Loan.
- Customer references remain within Loan/Banking integration contracts; no direct dependency on other product modules.

## Operational Areas
- Disbursement workflow.
- Installment schedule generation.
- Repayment posting.
- Early settlement quote.
- Loan statement view.
- Location-aware operational summary.

## Maintenance Rules
- Keep controllers small.
- Put business rules inside Services.
- Keep future reports under Modules/Loan/Reports or module-owned report controllers/views.
- Do not place Loan reports or utilities in other modules.
