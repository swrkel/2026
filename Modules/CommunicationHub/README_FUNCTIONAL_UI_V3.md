# Communication Hub Functional UI V3

This package improves the Communication Hub professional UI and keeps the main functionality pages operational.

## Deploy
Replace only:

```
Modules/CommunicationHub/
```

Then run:

```
php artisan optimize:clear
```

## SQL
If the tenant database does not yet have Communication Hub tables, run:

```
Modules/CommunicationHub/Database/SQL/00_RUN_THIS_IN_EACH_TENANT_DATABASE.sql
```

## Included operation guide

```
Modules/CommunicationHub/Documentation/DAY_TO_DAY_OPERATION_STEPS.md
```

## Main changes
- Professional dashboard spacing and prominent card design.
- Small margin/gap between KPI boxes.
- Root `/communication-hub` dashboard redesigned.
- Commercial dashboard redesigned.
- Added safe route alias for older `communicationhub.commercial.refills` links.
- No stored procedures in SQL.
- Tenant-safe operational data access retained.
