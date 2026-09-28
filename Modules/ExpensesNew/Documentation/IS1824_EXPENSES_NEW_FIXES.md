# IS1824 - Expenses New fixes (30 July 2026)

This package addresses all nine items in IS1824.

1. The Command Centre Settings tab now has a visible purple background and white text.
2. The Expense Summary table is centred and expanded.
3. The Categories table is centred and expanded.
4. Category Default Payee options are supplier names. `Cheque Module Not Enabled` is added as the first system option only while Chequer is disabled.
5. Add Expense now displays and saves only the Expense Account linked to the selected Category. The server validates the same link.
6. The Expense Account dropdown uses type-and-search Select2 behaviour.
7. Add Expense includes an Accounting Module dropdown and persists the selection in `expnew_expenses.accounting_module`.
8. List Expenses Add now opens the Add Expense form.
9. The List Expenses grid now uses a tenant/business-scoped server-side DataTables source so newly saved rows appear immediately.

## Database update

The module schema-readiness service applies the new nullable `accounting_module` column automatically. The same update is included as:

- Migration: `Database/Migrations/2026_07_30_000001_add_accounting_module_to_expnew_expenses_table.php`
- Raw idempotent SQL: `Database/sql/02_EXPNEW_IS1824_ACCOUNTING_MODULE_IDEMPOTENT.sql`

After replacing the module, clear Laravel caches:

```bash
php artisan optimize:clear
```
