Customers Standalone Module - Phase 3

Safe changes only. Contacts module files are not removed or changed.

Added:
- Customers dashboard: /customers/dashboard
- City-wise customer report: /customers/reports/city
- Standalone CSV export: /customers/export-csv
- Settings/status page: /customers/settings
- Future standalone sidebar partial inside the Customers module

Upload/replace only Modules/Customers.
Run:
php artisan view:clear
php artisan cache:clear
