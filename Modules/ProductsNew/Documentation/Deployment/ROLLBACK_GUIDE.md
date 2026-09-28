# Products New Rollback Guide

Preferred rollback during testing:

1. Remove Products New permissions from user roles.
2. Hide the Products New sidebar/menu item.
3. Clear route/config/view caches.
4. Keep `products_new_*` tables for audit and future retry.
5. Confirm the legacy Product module still opens and works.

Only drop Products New tables after explicit approval and after tenant backup.
