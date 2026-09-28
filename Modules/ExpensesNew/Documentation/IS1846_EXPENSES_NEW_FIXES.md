# IS1846 - Expenses New fixes (31 July 2026)

1. Add Expense helper messages use black text except validation/error messages.
2. `Cheque Module Not Enabled` is ensured and selected by default on a new expense when Chequer is disabled.
3. List Expenses now uses one Action dropdown containing Edit, Print, View and Delete.
4. Categories now uses one Action dropdown containing Edit, View and Delete.
5. Added business-scoped expense/category View routes and pages.
6. Category deletion is rejected when the category is used by existing expenses.

No database migration or raw SQL change is required.
