Customers Module Standalone Separation - Phase 4

Safe changes in this phase:
1. No files from the existing Contacts module were removed or changed.
2. Customers module still safely reads/writes contacts table where type = customer.
3. Added customer print profile page.
4. Added import template CSV download.
5. Added duplicate protection for customer code and email within the same business.
6. Updated settings page to show Phase 4 status.

Replace only:
Modules/Customers

After upload run:
php artisan view:clear
php artisan cache:clear

Test:
1. Open /customers
2. Click Import Template
3. Open customer Actions > Print Profile
4. Try saving same customer without changes and confirm: Nothing is changed to save.
5. Confirm Contacts module still works as before.
