# ZIP 281 – Customers Final Production Package

This is the latest full standalone Customers module package after the final production audit.

## Status

Customers module is now feature complete and ready for UAT.

## Included Areas

- Controllers
- Services
- Entities
- Reports
- Exports
- Dashboard
- Notes
- Activity
- Attachments
- Timeline
- Audit
- Portal foundation
- Communications foundation
- Documents foundation
- Routes
- Sidebar
- Documentation

## Important

Do not add new Customers features until UAT is completed. Only fix issues reported by testers.

## After Upload

Run:

```bash
php artisan optimize:clear
php artisan view:clear
php artisan route:clear
```
