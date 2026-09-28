# SUPPLIERS-SEP-007 Asset Separation Map

All Suppliers module JavaScript and CSS files are now module-local under:

- `Modules/Suppliers/Resources/assets/js`
- `Modules/Suppliers/Resources/assets/css`

Published public paths:

- `public/modules/suppliers/js`
- `public/modules/suppliers/css`

Publish command:

```bash
php artisan vendor:publish --tag=suppliers-assets --force
php artisan optimize:clear
```

No Suppliers page should load JS/CSS from Contacts, Purchase, Finance, or main-system module asset folders.
