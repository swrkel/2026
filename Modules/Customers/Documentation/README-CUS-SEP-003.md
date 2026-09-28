# CUS_SEP_003 Customer Audit / Attachments / Activity Separation

## Purpose
Moves Customer Notes, Documents, Audit Trail and Activity Timeline into standalone Customers module files.

## SQL
Run this once in each tenant DB before testing:

```sql
Customers/Database/sql/CUS_SEP_003_customer_audit_notes_attachments.sql
```

## Changed areas
- `Modules/Customers/Http/Controllers/CustomerNotesController.php`
- `Modules/Customers/Http/Controllers/CustomerDocumentController.php`
- `Modules/Customers/Http/Controllers/CustomerAuditController.php`
- `Modules/Customers/Http/Controllers/CustomerActivityController.php`
- `Modules/Customers/Services/CustomerAuditService.php`
- Customer-owned views under `Resources/views/notes`, `documents`, `audit`, and `activity`
- `Modules/Customers/Routes/web.php`

## Safety
No Contact, Petro, PetroPD, Finance, ledger calculations, or dealer portal core files were changed.

## Clear cache
```bash
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
```
