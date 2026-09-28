# Communication Hub RC2 - Functional Workflow Progress

## Deploy
Replace:
- `Modules/CommunicationHub/`
- `Modules/CoreUI/`

Run:
- `php artisan optimize:clear`

## SQL
Run in each tenant database if not already executed:
- `Modules/CommunicationHub/Database/SQL/00_RUN_THIS_IN_EACH_TENANT_DATABASE.sql`

No procedures, no definers, no delimiters, no triggers.

## Functional updates in RC2
1. Scheduled SMS page is now operational.
2. Scheduled SMS form saves messages as `status = scheduled` in the tenant database.
3. Delivery Reports page now has testing actions to mark messages as Sent, Failed, or Retry.
4. Process Pending button marks pending/scheduled messages as Sent for tester workflow validation.
5. Send SMS and Bulk SMS pages were cleaned into the professional Communication Hub card layout.
6. Tenant connection and business scope guards remain in place.

## Day-to-day test order
1. Communication Hub > SMS Clients: create a client.
2. Communication Hub > SMS Packages: create an SMS package.
3. Communication Hub > Credit Refills: refill a client wallet.
4. Communication Hub > Send SMS: send a single test SMS.
5. Communication Hub > Bulk SMS: queue multiple SMS.
6. Communication Hub > Scheduled SMS: create a scheduled SMS.
7. Communication Hub > Delivery Reports: mark messages as sent/failed/retry and verify status changes.
8. Communication Hub > Profit Reports: verify transaction/revenue/profit summaries.

## Notes
Actual external gateway delivery will be connected after provider setup is finalized. Current Send SMS workflow queues messages and supports status testing so users can verify the operational flow from UI.
