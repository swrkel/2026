# Communication Hub Stage 014 - Post Handover Stabilization

This package continues after Stage 013 and focuses on practical deployment/testing safety.

## Included

- Added missing sidebar/menu entries for Live Chat, Internal Messaging, Automation and Workflow sections.
- Expanded module permission configuration for SMS, Email, WhatsApp, Push, In-App, Chat, Automation, Workflow, Analytics, API Gateway and Audit Centre.
- Updated module registration metadata to include the complete Communication Hub channel set.
- Added SQL `15_POST_HANDOVER_MENU_PERMISSION_STABILIZATION.sql`.
- Added master SQL `MASTER_COMMUNICATION_HUB_SQL_STAGE_014_STABILIZATION.sql`.
- Added deployment health-check tables:
  - `communication_hub_deployment_checks`
  - `communication_hub_menu_health_logs`

## Deployment

1. Replace the `Modules/CommunicationHub` folder with this package content.
2. Run SQL file `15_POST_HANDOVER_MENU_PERMISSION_STABILIZATION.sql` in every tenant database.
3. If a tenant database is fresh, run `MASTER_COMMUNICATION_HUB_SQL_STAGE_014_STABILIZATION.sql` instead.
4. Clear Laravel caches after uploading files.

## Notes

The SQL is intended to be re-runnable. It does not delete existing records.
