556 – Customer Statement Module – 27 July 2026

Changed files
-------------
1. Routes/web.php
2. Providers/CustomerStatementsServiceProvider.php
3. Services/CustomerStatementWorkspaceAdapter.php
4. Services/CustomerStatementNumberingService.php
5. Http/Controllers/CustomerStatementNumberingController.php
6. Resources/views/partials/is1790_fixes.blade.php
7. Resources/lang/en/lang.php
8. Resources/lang/si/lang.php
9. Resources/lang/ta/lang.php
10. module.json
11. Documentation/VALIDATION_REPORT_556.txt

Corrections
-----------
- Every Customer Statement tab now activates and displays only its own pane.
- Statement Print Formats no longer displays the old Statement Payments filter/table section.
- Statement Print Formats uses the existing Customer Statement print/font-format editor.
- List Customer Statements hides every other pane, including Font Settings.
- Customer Statement Settings now uses the clear options Customer-wise and General.
- Customer-wise numbering stores an independent starting number for each selected customer.
- General numbering stores one starting number and calculates one common sequence for all customers in the business.
- The selected mode and starting number are loaded again after save.
- The statement creation page refreshes the next statement number according to the selected mode.
- Legacy hard-coded save/number URLs and incorrect field IDs are bypassed safely by module-owned endpoints.
- Duplicate legacy settings rows for the same business/customer are consolidated when that setting is saved again.
- No source file in Modules/Customers is overwritten.

Deployment
----------
Replace the complete Modules/CustomerStatements folder, or copy the changed files using the same paths.
Then run:

php artisan optimize:clear

Database
--------
No schema migration or raw SQL is required. The fix uses the existing customer_statement_settings table.
The module stores one configuration row with customer_id = 0; the original table has no foreign key on this column.
