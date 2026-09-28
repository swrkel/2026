S569 - Customer Statement Module - 30 Jul 2026

Fixed scope
===========
1. Customer Statements tab responds reliably.
2. Save Customer Statement works through the module-owned endpoint for the selected date range.
3. List Customer Statements tab responds and reloads the saved-statement table.
4. Numbering-code Delete is disabled when saved Customer Statement transactions use the code.
5. The DELETE endpoint repeats the usage check, so the protection cannot be bypassed by a crafted request.

Safety approach
===============
- No files under Modules/Customers are replaced.
- No global click handlers are removed.
- The page binds only namespaced handlers to the Customer Statement tabs and Save button.
- Only the two legacy DataTables GET URLs are redirected.
- No schema or SQL change is required.

Deployment
==========
Extract the changed-files ZIP at the Laravel project root, then run:

php artisan optimize:clear

Test with an authenticated tenant/business user.
