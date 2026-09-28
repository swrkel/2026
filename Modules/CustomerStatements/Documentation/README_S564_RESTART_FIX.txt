CUSTOMER STATEMENTS — S564 RESTART FIX
Date: 29 July 2026

SOURCE USED
- CustomerStatements(4).zip only.
- The previously delivered S564 package was not reused.

FIXED ITEMS
1. Customer Statements and List Customer Statements tabs
   - Removed the competing Bootstrap/shared fallback tab handlers from these links.
   - One CustomerStatements-owned handler now activates exactly one matching pane.

2. Statement Print Formats
   - The tab now opens the existing print/font format editor (#font_settings).
   - The unrelated Statement Payments pane (#list_statement_payments) is no longer exposed by this navigation.

3. Customer Statement numbering settings
   - Customer-wise mode stores one starting sequence per customer.
   - General mode stores one common starting sequence for the business.
   - Saved mode and starting values reload into the form and the settings list.
   - Older duplicate setting rows are read deterministically and consolidated when saved.
   - Next numbers use the highest trailing number already used, not a row count.

4. Alert Settings
   - A dedicated Alert Settings pane is used.
   - Font/print format controls are not rendered in Alert Settings.

5. Saved Customer Statements list
   - The shared Customers save engine remains authoritative for statement details.
   - The module persists the configured statement number and selected/resolved location after that save.
   - The List Customer Statements DataTable is refreshed once and opened after a successful save.
   - Existing statement rows with a missing location are repaired from their latest linked transaction/location, or the business default location.

SAFETY SCOPE
- No file under Modules/Customers is replaced.
- No database table is created or altered.
- Existing IS1790 table sizing and Statement Settings dropdown fixes were preserved.
- AJAX redirection is limited to the three exact Customer Statement save/list/settings endpoints.

DEPLOYMENT
1. Extract the changed-files ZIP from the Laravel project root.
2. Run: php artisan optimize:clear
3. Test in one tenant before deploying to all tenants.

DATABASE
No migration or raw SQL is required for this correction.
