# Communication Hub Stage 013 - Deployment & Testing Guide

## Scope
This parcel consolidates the Communication Hub work from SMS through final analytics/API/audit stages and adds a deployment verification SQL file.

## Upload / Replace
Replace the existing `Modules/CommunicationHub` folder with the `CommunicationHub` folder in this ZIP.

## SQL execution order per tenant database
Run the SQL files in this order inside each tenant database:

1. `01_COMMUNICATION_HUB_PERMISSIONS.sql`
2. `02_COMMERCIAL_SMS_PLATFORM_TABLES.sql`
3. `03_COMMUNICATION_HUB_INDEXES.sql`
4. `04_SMS_OTP_BUSINESS_SAFE_UPGRADE.sql`
5. `05_OTP_BUSINESS_PLATFORM_UPGRADE.sql`
6. `06_EMAIL_BUSINESS_PLATFORM_UPGRADE.sql`
7. `07_WHATSAPP_BUSINESS_PLATFORM_UPGRADE.sql`
8. `08_PUSH_NOTIFICATION_PLATFORM_UPGRADE.sql`
9. `09_IN_APP_NOTIFICATION_CENTRE_UPGRADE.sql`
10. `10_LIVE_CHAT_INTERNAL_MESSAGING_UPGRADE.sql`
11. `11_AUTOMATION_ENGINE_UPGRADE.sql`
12. `12_WORKFLOW_EVENT_ENGINE_UPGRADE.sql`
13. `13_ANALYTICS_API_GATEWAY_FINAL_AUDIT.sql`
14. `14_DEPLOYMENT_VERIFICATION_AND_HEALTHCHECK.sql` - read-only verification queries.

Use `MASTER_COMMUNICATION_HUB_SQL_STAGE_013_COMPLETE.sql` only if you want one combined script.

## Minimum test flow
1. Open Communication Hub dashboard.
2. Open SMS dashboard and send one test SMS to a test number.
3. Generate and verify one OTP.
4. Queue one email and confirm it appears in Email Queue.
5. Create one WhatsApp template/profile and queue a WhatsApp test message.
6. Register a push device test token and queue one push notification.
7. Create one in-app notification and confirm it appears in the inbox.
8. Open Live Chat and create/close one test conversation.
9. Create one automation rule and test event processing.
10. Create one workflow event/rule and confirm event log entry.
11. Open Analytics, Message History, Provider Performance, Campaign Performance, API Gateway, and Audit Centre.

## Multi-business checks
For two different business locations in the same tenant:
- Create a template in Business A and confirm it does not appear for Business B unless explicitly shared.
- Create messages from Business A and Business B and confirm report filters separate the results.
- Confirm queue, audit, and analytics pages respect `business_id` / location scope.

## Notes
- This module remains standalone under `Modules/CommunicationHub`.
- The SQL is designed for tenant databases. Do not hard-code database names.
- If your server uses cached routes/config/views, clear caches after uploading files.
