# CUS_023 Distribution Dealer Communication Center

Adds portal-only Notifications, Messages, Announcements, and Download Center pages under Modules/Customers.

Changed files:
- Modules/Customers/Routes/portal.php
- Modules/Customers/Http/Controllers/CustomerPortalController.php
- Modules/Customers/Services/CustomerLedgerService.php
- Modules/Customers/Resources/views/portal/layout.blade.php
- Modules/Customers/Resources/views/portal/partials_nav.blade.php
- Modules/Customers/Resources/views/portal/dashboard.blade.php
- Modules/Customers/Resources/views/portal/notifications.blade.php
- Modules/Customers/Resources/views/portal/announcements.blade.php
- Modules/Customers/Resources/views/portal/messages.blade.php
- Modules/Customers/Resources/views/portal/documents.blade.php

Test:
1. Login at /distribution-dealer/login.
2. Open Dashboard.
3. Verify menu has Notifications, Messages, Announcements, Documents.
4. Open each page and confirm only the logged-in dealer context is shown.
5. Confirm ERP sidebars are not loaded.
