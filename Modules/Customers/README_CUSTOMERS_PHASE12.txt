Customers Module - Phase 12
Standalone Permission Foundation

Date: 2026-06-04

This package continues Customers module separation safely.

Included:
1. Standalone Customers permission seed foundation:
   - customers.dashboard
   - customers.view
   - customers.create
   - customers.edit
   - customers.delete
   - customers.import
   - customers.export
   - customers.notes.view/create/delete
   - customers.attachments.view/create/delete
   - customers.documents.view/create/delete
   - customers.document_categories.view/create/delete
   - customers.timeline.view
   - customers.audit.view
   - customers.reports.view
   - customers.settings.view

2. CustomersDatabaseSeeder updated to call CustomersPermissionSeeder.

3. Customers permission config added:
   Modules/Customers/Config/permissions.php

4. Sidebar updated to use the new Customers permissions, while keeping existing Contacts/customer permissions as fallback.
   This prevents current users from losing access while roles are gradually updated.

Important:
- No Contacts files are removed or changed.
- Contacts remains working.
- Customers module continues to use contacts.type = customer as the master customer source until the final data migration phase.
- Multi-branch and head-office centralized logic from earlier phases is preserved.

Optional SQL/Artisan:
If your server supports module seeders, run:
php artisan db:seed --class="Modules\\Customers\\Database\\Seeders\\CustomersPermissionSeeder"

If you do not run the seeder immediately, the sidebar still works using existing old permissions as fallback.

After replacing files, run:
php artisan view:clear
php artisan cache:clear
php artisan route:clear
