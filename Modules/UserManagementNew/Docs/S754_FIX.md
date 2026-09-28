# S754 – Managed Role Sidebar Visibility Fix – 17 Sep 2026

## Issue fixed
A role created in **User Management New → Roles & Permissions** could be limited to one module (for example **Petro PD**) but the logged-in user could still see unrelated sidebar parents such as **User Management New**, **Finance** and **Help Guide**.

## Correction
The `umn.*` permissions of a role carrying `umn.managed` are now the authoritative visibility contract.

- Managed module **View** controls whether the sidebar parent may be shown.
- Managed page/tab **View** controls supported child links.
- Legacy roles and direct user permissions can no longer widen the sidebar of a managed role.
- Direct URL access continues to be checked server-side by `EnforceManagedRolePermissions`.
- User Management New's own sidebar now applies the same managed module/page checks server-side.
- Legacy sidebar parents that have not yet adopted `data-sidebar-module` are covered by a conservative exact-title fallback.
- Role and user-role saves now clear permission/sidebar caches immediately.
- Super Admin and the protected Business Admin bypass behaviour is preserved.

## Database
No SQL or database change is required.

## Deployment
1. Back up the current `Modules/UserManagementNew` folder.
2. Replace it with this corrected module folder.
3. From the Laravel application root run:

```bash
php artisan optimize:clear
```

4. Log out the test user completely, then log in again.
5. Open **User Management New → Roles & Permissions → PD Operation → Edit** and confirm only the intended Petro PD module/pages are selected.
6. Log in as the PD Operation user. Only the permitted module/pages should be visible in the sidebar. Unpermitted mapped URLs should return HTTP 403.

## Validation completed on parcel
All PHP files in the module were checked with `php -l`; no syntax errors were found.
