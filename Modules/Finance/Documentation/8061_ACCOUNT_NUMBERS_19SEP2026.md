> **Superseded on 20 Sep 2026:** Finance no longer owns the Account Number starting map. The authoritative source is now **Super Admin -> Super Admin Settings -> Default Accounts -> Account Numbers**. See `SUPERADMIN_ACCOUNT_NUMBER_SOURCE_20SEP2026.md`. The historical notes below are retained only to document the earlier implementation.

# Task 8061 - Finance Account Numbers

Implemented in Finance -> List Accounts -> Account Settings.

## Requirements implemented

1. Added **Account Numbers** button to Account Settings.
2. Popup shows **Account Types** on the left and **Map Account Starting Nos** on the right.
3. Default starting numbers:
   - Assets: 10000
   - Liabilities: 20000
   - Income: 30000
   - Expenses: 40000
   - Equity: 50000
4. Child/sub account types inherit the number series of their top-level Account Type.
5. Add Account loads the next Account Number automatically.
6. Account Number is read-only in Add Account and Edit Account.
7. Server recalculates/controls the Account Number, so changing the browser request cannot override it.
8. Users with Account Settings permission can change the five starting numbers.
9. Saving changed starts immediately renumbers all existing Accounts in the five mapped series in one DB transaction.
10. The List Accounts DataTable refreshes immediately after renumbering.
11. Existing S769 calendar and 19 Sep one-click Add Account fixes are retained because this parcel is based on the latest Finance parcel.

## Database

New Finance-owned table: `finance_account_number_settings`.

Use either:

```bash
php artisan migrate
```

or import:

`Finance/Database/SQL/8061_FINANCE_ACCOUNT_NUMBER_SETTINGS.sql`

Then run:

```bash
php artisan optimize:clear
```
