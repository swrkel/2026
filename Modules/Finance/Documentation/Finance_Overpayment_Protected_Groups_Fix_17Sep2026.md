# Finance Overpayment / Protected Account Groups Fix — 17 Sep 2026

## Required business rule
Users may Transfer, Pay, or Deposit an amount greater than the selected source account's current balance for normal Finance accounts.

The available-balance restriction applies only to accounts linked to these account groups:

- Cash / Cash Account
- Card / Cards
- Cheques in Hand / Cheques in Hand (Customer's)

Protected accounts cannot be taken below their available balance by Transfer, Pay, Deposit, or Cheque Deposit operations.

## What was corrected

1. Reworked `FinanceTransactionGuard` so the decision is made per source account group rather than for every account.
2. Removed the previous subscription-wide/SW over-deposit bypass from the protected source-account rule.
3. Deposit now allows overpayment for non-protected source accounts and blocks it for protected groups.
4. Transfer now allows overpayment for non-protected source accounts and blocks it for protected groups.
5. Expense/Pay create and edit use the same protected group rule.
6. Cheque Deposit continues to enforce balance because Cheques in Hand is a protected group.
7. AJAX account-balance responses now expose whether the selected source account is protected so the browser preview and server validation stay consistent.
8. Older `/deposits-module` compatibility routes were aligned with the same rule.

## Changed files

- `Services/Transactions/FinanceTransactionGuard.php`
- `Http/Controllers/Account/Concerns/HandlesDepositsAndTransfers.php`
- `Http/Controllers/Account/Concerns/HandlesCheques.php`
- `Http/Controllers/Account/Concerns/ReportsAccountBalances.php`
- `Http/Controllers/Deposits/DepositsController.php`
- `Http/Controllers/Expenses/Concerns/CreatesExpenses.php`
- `Http/Controllers/Expenses/Concerns/UpdatesExpenses.php`
- `Resources/views/account/deposit.blade.php`
- `Resources/views/account/transfer.blade.php`
- `Resources/views/account/cheque_deposit.blade.php`
- `Resources/views/deposits/deposit.blade.php`

## Database changes
None.

## Recommended deployment
Replace the Finance module with the corrected parcel, then run:

```bash
php artisan optimize:clear
```

## Suggested acceptance tests

1. Transfer from a normal non-protected account with balance 100.00, amount 150.00 → must save.
2. Deposit from a normal non-protected source account with balance 100.00, amount 150.00 → must save.
3. Pay an expense from a normal non-protected account with balance 100.00, amount 150.00 → must save.
4. Repeat each test from an account linked to Cash Account → amount above balance must be blocked.
5. Repeat from an account linked to Card → amount above balance must be blocked.
6. Repeat from an account linked to Cheques in Hand (Customer's) → amount above balance must be blocked.
7. For all protected groups, amount equal to or below available balance → must save normally.
