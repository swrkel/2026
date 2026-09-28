# Products New Tenant Rollout Guide

1. Backup the tenant database.
2. Run `MASTER_PRODUCTSNEW_SQL.sql` on the selected tenant database.
3. Clear Laravel caches if the server uses cached routes/config/views.
4. Enable `products_new.*` permissions only for testing users first.
5. Open legacy Product module and confirm it still works.
6. Open Products New dashboard and each testing page.
7. Validate add/edit/list/report/import/export flows.
8. Approve sidebar switch only after user acceptance testing.

Do not delete or disable the legacy Product module until Products New is fully approved.
