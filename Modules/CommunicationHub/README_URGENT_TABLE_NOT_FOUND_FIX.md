# Communication Hub urgent table-not-found fix

Fixes: SQLSTATE[42S02] table `communication_hub_messages` missing / central DB query issue.

## Replace
Upload/replace only:

```
Modules/CommunicationHub/
```

## SQL
Run this in the affected TENANT database, not central DB:

```
Modules/CommunicationHub/Database/SQL/00_RUN_THIS_IN_EACH_TENANT_DATABASE.sql
```

This SQL contains no stored procedures, no DEFINER, no DELIMITER, no CALL statements.

## Clear cache

```
php artisan optimize:clear
```

## Notes
- Tenant operational routes are now loaded from `Routes/tenant.php` with Stancl tenancy middleware.
- Central `Routes/web.php` does not run Communication Hub dashboard/message queries.
- Dashboard now shows an installation warning instead of throwing HTTP 500 if tables are missing.
- Dashboard counts are scoped by `business_id` where the column exists.
