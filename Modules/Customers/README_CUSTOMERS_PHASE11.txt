Customers Module Standalone Separation - Phase 11

Replace/upload:
- Modules/Customers
- resources/views/layouts/partials/sidebar-sections/sidebar-customers.blade.php

What Phase 11 adds:
1. Enterprise Customers Dashboard expansion.
2. Multi-branch / location KPI filter.
3. Customers by Branch report.
4. Customer Activity report.
5. Customer Attachments report.
6. Customer Documents report.
7. Customer Audit Trail report.
8. Expanded standalone Customers sidebar menu.

Safe separation notes:
- No Contacts module files are removed.
- No Contacts routes are changed.
- Customers still safely use contacts.type = customer as the master customer source.
- Customer-specific notes, documents, attachments, activity and audit features remain inside Modules/Customers.

After upload run:
php artisan view:clear
php artisan cache:clear
php artisan route:clear
