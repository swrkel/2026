# Cheque Deposit and Account Settings Fix

Date: 15 September 2026

## Resolved issues

1. The Cheque Deposit popup now reads outstanding cheques from every active
   Cheques in Hand account belonging to the current business. The selected
   range is applied to the effective cheque date shown in the table. Cheque
   metadata is resolved from the ledger first and from linked payment records
   when needed.
2. Finance > List Accounts > Account Settings now uses the Finance module's
   own view and a deterministic DataTables JSON endpoint. Errors return valid
   JSON and release the Processing indicator instead of leaving the page stuck.

## Account Settings follow-up correction

The Account Settings AJAX request was completing, but the Action column tried
to call the List Accounts table's private `financeAccountActionDropdown()`
renderer. That function is outside the Account Settings script scope, so the
first returned row raised a JavaScript ReferenceError during drawing and left
the Processing indicator displayed. Account Settings now renders its own Edit
button directly, guards reloads until its table exists, and keeps its filters
inside the Account Settings lifecycle.

## Deployment

1. Back up the existing `Modules/Finance` directory.
2. Replace it with the `Finance` directory in this archive.
3. Run `php artisan optimize:clear` from the application root.
4. Hard-refresh the browser.

No database migration or SQL script is required.

## Acceptance checks

1. Open Finance > List Accounts and confirm the accounts list loads.
2. Open Account Settings and confirm the table exits Processing and displays
   rows (or an empty table when there are no settings).
3. Open Cheque Deposit, select a date range containing known cheque dates, and
   confirm the outstanding Cheques in Hand rows appear.
4. Select and deposit one cheque, reopen the popup, and confirm the deposited
   cheque is no longer offered.
