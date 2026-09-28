# Expenses New - Expense Category Finance Expense Account filter - 21 Sep 2026

## Requirement
Expenses New > Expense Category > Add/Edit > Expense Account must show all relevant accounts from Finance > List Accounts, and only accounts classified under the Finance Expense/Expenses Account Type (including its sub-types).

## Root cause
Expenses New previously looked for legacy columns such as `accounts.account_type` / `accounts.account_category`. The current Finance module classifies List Accounts using `accounts.account_type_id` linked to `account_types.id`. On current schemas the old check could therefore fail to filter and sync unrelated accounts.

## Change
- Finance account types named `Expense` or `Expenses` are resolved per business.
- All descendant account sub-types are included.
- Matching non-disabled, non-deleted Finance accounts are synchronised to `expnew_expense_accounts` by `external_account_id`.
- Previously synced Finance accounts that are disabled/deleted or moved out of Expense type are made inactive in Expenses New.
- Add/Edit Expense Category refreshes this sync whenever the form opens, so newly added Finance Expense accounts appear immediately.
- The dropdown only renders Finance-synchronised expense accounts (`external_account_id` present).
- No Finance PHP class/controller is referenced; integration remains database-level and standalone-safe.

## Database
No new table, column, SQL, or migration is required.
