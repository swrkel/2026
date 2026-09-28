# User Management New

This standalone module uses the application's existing `users`, `roles`,
`permissions`, `model_has_roles`, `model_has_permissions` and
`role_has_permissions` tables. No database migration or SQL import is required.

Permission precedence is:

1. Manage Side Bar controls whether a module is available.
2. Manage Page controls which menu pages and tabs are available.
3. User Management New assigns the remaining module/page permissions to a role.

New roles created here receive the `umn.managed` marker. Only those roles are
enforced by the module middleware, preserving compatibility with legacy roles
until an administrator intentionally edits them here.

## New module integration contract

Every new module must provide `Config/module_permissions.php`. Return one module
parent plus every sidebar page and internal tab that needs independent access:

```php
return [
    ['key' => 'example_module', 'label' => 'Example Module', 'type' => 'module'],
    ['key' => 'example_dashboard', 'label' => 'Dashboard', 'type' => 'page'],
    ['key' => 'example_orders', 'label' => 'Orders', 'type' => 'page'],
    ['key' => 'example_orders_items', 'label' => 'Items', 'type' => 'tab'],
];
```

Keys must be stable, lowercase and unique. Menu-page routes must be present in
the module sidebar or registry metadata so direct URL permission enforcement can
map the request to the same key. Do not add minor operations such as create,
edit, delete or print to this manifest; User Management New supplies the global
View, Edit, Delete, Print, PDF, Email and WhatsApp action rights automatically.

## Deployment

Extract the changed-files package at the Laravel application root, preserving
paths, then run:

```bash
php artisan optimize:clear
```
