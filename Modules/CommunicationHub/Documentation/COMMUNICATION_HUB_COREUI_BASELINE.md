# CommunicationHub CoreUI Baseline

This package introduces the professional UI baseline for CommunicationHub and adds the reusable CoreUI module.

Deployment:
1. Upload Modules/CoreUI.
2. Upload Modules/CommunicationHub.
3. Add these entries to modules_statuses.json if not already present:
   - "CoreUI": true
   - "CommunicationHub": true
4. Run php artisan optimize:clear.

SQL:
- Run Database/SQL/00_RUN_THIS_IN_EACH_TENANT_DATABASE.sql in each tenant database if not already run.
- 01_COMMUNICATION_HUB_PERMISSIONS.sql is for permissions.
- 02_DEFAULT_DATA.sql is reserved for default packages/templates/providers.
- 03_INDEXES.sql contains optional performance indexes.
- 99_ROLLBACK.sql is destructive and only for rollback after backup.

Design Standard:
CommunicationHub now starts using CoreUI style components: page headers, KPI cards, cards, table styling, badges and professional spacing.
CoreUI contains no business logic and no tenant data queries.
