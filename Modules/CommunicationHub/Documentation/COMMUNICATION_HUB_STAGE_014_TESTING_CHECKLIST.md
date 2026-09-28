# Communication Hub Stage 014 Testing Checklist

Use this checklist after uploading the module to the server.

## Menu / Permission

- Communication Hub main sidebar item is visible for users with `communicationhub.dashboard.view`.
- SMS, Email, WhatsApp, Push, In-App, Live Chat, Automation, Workflow, Analytics, API Gateway and Audit pages open without `RouteNotFoundException`.
- Users without Communication Hub permissions do not see restricted pages.

## Business Scope

For two different businesses in the same tenant:

- Create an SMS template in Business A and confirm it is not visible in Business B.
- Create a WhatsApp profile in Business A and confirm it is not visible in Business B.
- Create an automation rule in Business A and confirm it is not visible in Business B.
- Send or queue a test message from Business A and confirm reports do not show it in Business B.

## Channel Smoke Tests

- SMS: send, bulk queue, scheduled queue, delivery report.
- OTP: generate, resend, verify, fail attempts.
- Email: compose, bulk queue, retry.
- WhatsApp: send, template save, scheduled queue.
- Push: device registration, send push, scheduled queue.
- In-App: send alert, inbox view, mark read, archive.
- Live Chat: create conversation, reply, close.
- Internal Messaging: send and mark read.
- Automation: create rule, test event, process pending.
- Workflow: register event, create rule, process event log.
- Reports: analytics, message history, provider performance, campaign performance.
- API Gateway: create token and check API request log.

## SQL Verification

Run:

```sql
SELECT * FROM communication_hub_deployment_checks ORDER BY id DESC LIMIT 10;
SELECT COUNT(*) FROM communication_hub_menu_health_logs;
```

The first query should show `stage_014_sql_loaded` as `pass` after SQL execution.
