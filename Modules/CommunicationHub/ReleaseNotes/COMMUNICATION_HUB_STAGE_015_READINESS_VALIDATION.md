# Communication Hub Stage 015 - Readiness Validation

This stage adds a practical readiness screen for server-side validation after upload.

## Added
- `CommunicationHubReadinessController`
- `communication-hub/readiness-check` route
- Menu entry: Readiness Check
- Permission key: `communicationhub.readiness.view`
- Tenant SQL: `16_READINESS_ROUTE_MENU_VALIDATION.sql`
- Master SQL: `MASTER_COMMUNICATION_HUB_SQL_STAGE_015_READINESS_VALIDATION.sql`

## Purpose
Use this page after deploying the module to confirm that tenant tables and important routes are available before functional testing.

## Important
Run the SQL in each tenant database where Communication Hub is enabled. Then clear route/config/view cache before checking the page.
