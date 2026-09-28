# FIN-010 Upload Notes

Upload only the included `Finance` folder over the existing module copy.

Do not delete old main-system controllers yet. Those will be removed only in FIN-011 after FIN-010 standalone validation and UAT.

After upload, run:

```bash
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
php artisan config:clear
```

Smoke test:

1. Finance dashboard
2. List Accounts
3. Account Books
4. Customer/Supplier payments
5. Cheque deposit and cheque write
6. Journal entries
7. Finance reports

