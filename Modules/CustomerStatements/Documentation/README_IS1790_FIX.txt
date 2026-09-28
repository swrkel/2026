IS1790 – Customer Statements – 26 July 2026

Changed files
-------------
1. Services/CustomerStatementWorkspaceAdapter.php
2. Resources/views/partials/is1790_fixes.blade.php

Corrections
-----------
- Customer Statements table now fits all 15 columns within the available page width.
- List Customer Statements table now fits all 10 columns, including Description.
- Both tables recalculate their DataTables widths after load, tab changes and window resizing.
- Statement Settings Add/Edit Alignment and Text Position use reliable native selects inside AJAX modals.
- No Customers module source file is overwritten; the fixes are owned by CustomerStatements.

Deployment
----------
Copy the changed files into Modules/CustomerStatements using the same folders.
Then run:

php artisan optimize:clear

Database
--------
No migration or raw SQL query is required for IS1790.
