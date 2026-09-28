# CUS-005 Customer Register Standalone Actions - Phase 1

This package starts moving Customer action functions away from the old Contact module.

## Added
- CustomerPaymentController
- CustomerLoanController
- CustomerRefundController
- CustomerDepositController
- CustomerLedgerController
- CustomerInfoController
- CustomerDocumentController
- CustomerAuditController
- CustomerNotesController
- Standalone Customers module routes for all action dropdown items
- Customer module views for payment, loan, refund, deposit, ledger, balance, contact info, notes, documents and audit
- Updated Customer Register action dropdown so it no longer links to ContactController or contact module views

## Important
This is Phase 1. It creates the safe standalone route/controller/view structure. Posting of payment/loan/refund/deposit forms is intentionally guarded until account mapping is verified, to avoid breaking accounting entries.

## Test
1. Open Customers Module > Customer Register.
2. Click Actions.
3. Verify each action opens a Customers module URL under `/customers/{id}/...`.
4. Verify no action opens `/contacts/...` or `ContactController` pages.
5. Check View/Edit/Delete still work.
