# ZIP 279 – Customers Release Candidate Standalone Module

This is the full Customers standalone module after ZIP 278 production-candidate audit.

## Use this package as the latest full Customers module.

After upload run:

```bash
php artisan optimize:clear
php artisan view:clear
php artisan route:clear
```

## Tester focus

- Customers list
- Add/Edit/View customer
- Customer ledger
- Customer statement
- Aging report
- Activity report
- Dashboard
- Exports / print
- Sidebar routes
- Permissions
