Customers Module Standalone Separation - Phase 13
================================================

Purpose
-------
Phase 13 adds the multi-branch security foundation for the standalone Customers module.

What is included
----------------
1. Centralized business_id control remains unchanged.
2. Branch/location visibility is now applied from CustomerService.
3. Superadmin, Business Admin, and users with access_all_locations can see all customer branches.
4. Branch users only see customers assigned to their permitted location permissions, such as location.{id}.
5. Customer register, dashboard, reports, notes, attachments, documents, and audit summaries now respect the same customer visibility foundation.
6. No Contacts files are removed or changed.
7. Existing contacts table remains the customer master source for now.

Files updated
-------------
Modules/Customers/Services/CustomerService.php
Modules/Customers/Http/Controllers/CustomerDashboardController.php
Modules/Customers/Http/Controllers/CustomerReportController.php

Upload instructions
-------------------
Replace/upload:
Modules/Customers
resources/views/layouts/partials/sidebar-sections/sidebar-customers.blade.php

Then run:
php artisan view:clear
php artisan cache:clear
php artisan route:clear

Database
--------
No new SQL is required for this phase.

Important note
--------------
If your contacts table has business_location_id or location_id, branch security is applied.
If the table does not yet have either column, the module remains safely centralized by business_id until the final customer table migration phase.
