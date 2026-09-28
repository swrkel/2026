LA-1132 – Customer Payments – 5 Aug 2026

Scope
-----
1. Customers Module > Payments Received
   - Date-range selection returned a DataTables Ajax warning and payment rows did not load.
2. Customers Module > Customer Payments > List Customer Payments
   - Payment rows did not load.

Root causes corrected
---------------------
- Both listing queries grouped rows while selecting non-aggregated columns. Tenant DB connections use strict SQL mode, so ONLY_FULL_GROUP_BY rejected the generated SQL.
- Payments Received requested `paid_for` and `bank_name`, but the server response did not provide both fields.
- List Customer Payments still used legacy root URLs for supporting Ajax requests instead of Customers-module routes.
- List Customer Payments used the removed `act.interest` table alias for the Interest DataTables column.
- The location and daily-shift filters sent by the page were not applied by the list query.

Files changed
-------------
Modules/Customers/Http/Controllers/CustomerStandaloneOutstandingController.php
Modules/Customers/Http/Controllers/CustomerStandalonePaymentController.php
Modules/Customers/Resources/views/contact/outstanding_received_report.blade.php
Modules/Customers/Resources/views/customer_payments/index.blade.php

Database
--------
No migration or raw SQL is required.

Deployment
----------
Replace the changed files while preserving paths, then run:
php artisan optimize:clear
