# IS1834 – Expenses New – 30 July 2026

## Corrections

1. **Saved expenses are visible in List Expenses**
   - The List Expenses page now renders the latest business-scoped records on the server.
   - DataTables is initialised inside the page content instead of relying on an optional layout script stack.
   - The server-rendered rows remain available as a fallback if DataTables or its JavaScript assets are unavailable.

2. **Accounting Module is selectable and filtered by Payment Method**
   - The existing `expnew_expenses.bank_account_id` field stores the selected Finance account.
   - Cash shows Cash-related accounts, Card shows Card-related accounts, Cheque shows Cheque/Bank accounts, and Bank Transfer shows Bank accounts.
   - The selection is type-and-search enabled through the existing Select2 component.
   - The controller validates that the selected account belongs to the active business and is valid for the chosen payment method.

3. **Expenses are posted to the related Finance Account Books**
   - Debit: linked category expense account for the full expense amount.
   - Credit: selected payment account for the paid amount.
   - Credit: Accounts Payable for any unpaid amount.
   - Entries are idempotent and identified by `EXPNEW-{expense_id}-...` slip numbers, preventing duplicates on edit/save.
   - Deleting an expense removes its Expenses-New account-book entries.

## Database

No new migration or raw SQL is required. Existing `bank_account_id`, shared `accounts`, and shared `account_transactions` structures are used.
