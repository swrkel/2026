# Finance Reports Module - Correctness Audit & Fixes

Prepared: 18 Aug 2026
Baseline: `FinanceReports(3).zip`
Scope: Standalone `Modules/FinanceReports` reporting module only.
Database changes: **None**. No migration or raw SQL is required for this release.

## 1. Profit & Loss / Income Statement classification

- Replaced keyword-first account classification with the configured Account Type hierarchy.
- An account with a valid Asset/Liability/Income/Expense/Equity type is classified by that type first; Account Group/display-name text cannot override it.
- Fixes the reported case where **Accounts Receivable** (Current Asset) appeared under P&L Income because its Account Group was named **Credit Sales**.
- P&L now accepts only Income and Expense accounts.
- Revenue is separated from Other Income.
- COGS is separated from other expenses.
- Gross Profit = Sales/Operating Revenue - COGS.
- Net Profit = Total Income - Total Expenses.
- Income uses credit-minus-debit; expenses use debit-minus-credit.
- Revenue Analysis now requests the Revenue subset, not all Income accounts.

## 2. Trial Balance

- Corrected Trial Balance from period debit/credit turnover to **closing account balances as at the selected date**.
- Each account is netted first; debit balances show in Debit and credit balances show in Credit.
- Abnormal/contra balances naturally appear on the opposite side rather than as misleading negative balances.
- Trial Balance filter is now an explicit **As At Date**.
- Financial pack/engine calls now use the as-at date consistently.

## 3. Balance Sheet

- Assets, Liabilities and Equity are selected from the Account Type hierarchy.
- `show_in_balance_sheet` is respected where available.
- Current Assets and Current Liabilities are identified separately.
- Working Capital = Current Assets - Current Liabilities.
- Current Ratio = Current Assets / Current Liabilities.
- Added **Current / Unclosed Earnings (Loss)** to Equity from cumulative unclosed Income/Expense balances so the accounting equation can reconcile before year-end closing entries are posted.
- Balance Sheet Difference is Assets - (Liabilities + Equity).

## 4. Mandatory location-wise reporting correction

The supplied database schema does not contain `account_transactions.location_id`. The module no longer treats this as permission to use business-wide data in a selected branch report.

Location is resolved in this order:

1. `account_transactions.location_id` when a tenant schema actually has it.
2. The directly linked `transactions.location_id` (or `business_location_id`).
3. The transaction linked through `transaction_payments`.
4. Only for truly unlinked rows, a branch-specific `accounts.location_id` may be used.

Important safeguards:

- A global account with `accounts.location_id = 'all'` is **not copied into every branch**.
- If a location cannot be proven, the row is not guessed into a branch.
- Consolidated mode still includes valid business-wide/unallocated postings.
- Branch Performance and Consolidation Center disclose **Unallocated / Global (No resolvable source location)** differences when required so physical branch totals can reconcile transparently to consolidated totals.
- The same strict location rules are used by Trial Balance, Balance Sheet, P&L, Income Statement, Account/General Ledger, Cash/Bank books, Journal Register, Day Book, dashboards, ratios, cash flow, audit reports and related statement queries.
- Budget-vs-Actual does not apply a business-wide budget to one branch if the budget table has no location dimension.
- Fixed Asset/Asset Movement branch reports do not fall back to business-wide data when no trustworthy asset location source exists.
- Bank Reconciliation does not use a business-wide statement balance for a selected branch when the statement table has no location field.

## 5. Active/deleted/reversed transaction handling

- Common financial queries exclude soft-deleted accounting transactions.
- `new_deleted_at`, `reversed` and `journal_deleted` flags are also respected where those columns exist.
- Linked source transactions/payments are checked for active status.
- Audit reports separately retain deleted/reversed rows where that is the purpose of the report.

## 6. Cash / Bank / Treasury reports

- Cash and Bank account selection uses configured account groups/default classifications rather than broad name matching.
- Prevents unrelated accounts such as Bank Charges or Cash Discount from being treated as cash/bank accounts merely because of their names.
- Cash Flow is reconciled to actual Cash/Bank account movements.
- Bank Reconciliation shows a safe book-only result when independent statement data is unavailable or cannot be branch-scoped.
- Cheque/Post-Dated Cheque registers use cheque date when available and apply branch/deletion filters.

## 7. Receivable / Payable / Customer & Supplier reports

- Returns/Credit Notes reduce receivable/payable exposure.
- Returns are shown separately in Outstanding/Summary/Statement reports.
- Customer/Supplier Statements are true movement statements: invoices increase the balance; dated payments and returns reduce it.
- Contact records with `type = both` are supported.
- **Contact opening balances (`transactions.type = opening_balance`) are now included** in receivable/payable balances and statements.
- Payments linked to opening-balance transactions are included.
- A return explicitly processed at another branch does not reduce the selected branch's outstanding balance; missing return location may inherit the already-scoped source invoice location.

## 8. Fixed Assets

- Fixed Asset reports adapt to the actual available schema (`fixed_assets` / `assets`) instead of assuming columns that do not exist.
- The supplied `fixed_assets` structure can use `date_of_operation`, `asset_location` and `amount`.
- Asset movement values use movement amount when available, otherwise quantity x asset unit price where the `asset_transactions` + `assets` schema supports it.
- Branch filtering uses actual asset/movement location fields; it never silently becomes business-wide.

## 9. Audit / control reports

- Corrected the shared audit row mapping so each report's headers match the data returned.
- Audit Trail combines Posted, Edited and Deleted/Reversed events.
- Transaction History shows correct Type, Amount, Created By, Updated By and Reference values.
- Deleted Transactions includes soft-deleted, `new_deleted_at`, reversed and journal-deleted accounting rows.
- Edited Transactions now filters actual updated entries rather than showing every transaction.
- Financial Log Viewer columns match their report headings.
- Audit location scoping uses direct/payment-linked source transaction locations with the same no-guessing rule as financial statements.

## 10. Executive / Intelligence / KPI calculations

- Executive Dashboard Revenue now uses Sales/Operating Revenue, not Total Income.
- Working Capital uses Current Assets - Current Liabilities.
- Current Ratio uses Current Assets / Current Liabilities.
- Net Margin uses Net Profit / Revenue.
- CFO Dashboard Gross Profit now uses actual Gross Profit rather than Net Profit.
- Financial Intelligence separates Revenue, Other Income and Gross Profit.
- Scenario Analysis keeps Other Income unchanged unless explicitly modelled, and bases projected profit on Revenue + Other Income - Expenses.
- Executive KPI Liquidity and Working Capital calculations use current assets/current liabilities.
- Removed the incorrect performance recommendation to index a non-existent `account_transactions.location_id`; source-location indexes belong on `transactions.location_id` while account transaction indexes use transaction/payment linkage columns.

## 11. Report filters / UI correctness

- Removed the toolbar from the shared filter partial so reports no longer render duplicate export/print toolbars.
- All report pages using the shared filter now include exactly one toolbar explicitly.
- Shared filter variables have safe defaults to prevent missing-variable errors.
- Date ranges use the ERP financial year convention **1 April - 31 March**.
- **This FY** correctly starts in the previous calendar year when the current date is January-March.
- **Last FY** is calculated from the resolved current financial year.

## 12. Validation completed

- Full PHP syntax validation: **102 PHP files checked; 0 syntax errors**.
- Static route-to-controller validation: **92 route actions / 92 unique route names; 0 missing controller actions**.
- Static controller-view validation: **62 referenced Finance Reports views; 0 missing views**.
- Static Blade route-reference validation: 0 missing Finance Reports routes.
- Static configuration route-reference validation: 0 missing Finance Reports routes.
- Shared-filter toolbar validation: **38 actual shared-filter report views; exactly one toolbar on each; 0 toolbars inside the shared filter partial**.
- Debug/conflict-marker scan: no `dd()`, `dump()`, `var_dump()`, `die()` calls or merge-conflict markers in the final module.
- Schema-driven checks confirmed the supplied database has no `account_transactions.location_id` but does have `transactions.location_id`.
- Schema-driven checks confirmed the reported Accounts Receivable account is a **Current Asset** even though its Account Group is **Credit Sales**.

## Deployment

1. Back up the current `Modules/FinanceReports` folder.
2. Replace it with the `FinanceReports` folder from the corrected package.
3. From the Laravel application root run:

```bash
php artisan optimize:clear
```

4. Verify one known location/date using:
   - Account Book closing balance vs Trial Balance.
   - P&L: Accounts Receivable must not appear under Income.
   - Balance Sheet: Accounts Receivable should appear under Assets where it has a balance.
   - Selected branch totals vs the same source transactions.
   - Consolidated report and any disclosed Unallocated/Global row.

## Notes

This correction is read-only reporting logic. It does not alter existing accounting postings or historical data. Final amount verification against a live tenant database should be performed after deployment because the package audit is schema/code based and does not execute against the production tenant connection.
