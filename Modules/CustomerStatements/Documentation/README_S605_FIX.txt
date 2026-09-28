S605 - Customer Statement - 4 Aug 2026
======================================

Scope
-----
1. Customer Statements tab: show each bill's transaction date in the Date column.
2. Customer Statements and List Customer Statements: increase table and DataTables toolbar font sizes by 50%.
3. List Customer Statements: move Statement No immediately after Action.

Implementation
--------------
- Added a module-owned bill DataTable endpoint. It delegates to the existing Customers statement engine, then fills only blank Date values from transactions.transaction_date within the current business.
- Added the bill endpoint to the existing narrow AJAX redirect layer; no Customers module source file is overwritten.
- Reorders the saved-statement DataTable by rebuilding it once from its original initialization options, preserving filters, buttons, AJAX, callbacks, and row data.
- Changed the enforced table fonts from 9px to 13.5px and from 10px to 15px. Responsive values were also increased by exactly 50%.

Deployment
----------
Extract the changed-files archive from the Laravel project root, then run:

    php artisan optimize:clear

Database
--------
No migration or raw SQL is required.
