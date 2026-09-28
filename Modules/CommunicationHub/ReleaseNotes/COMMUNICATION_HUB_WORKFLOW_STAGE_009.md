# Communication Hub Stage 009 - Workflow Rules & Event Engine

This stage adds a business-safe workflow event engine on top of the Automation stage.

## Included
- Workflow Event Engine dashboard
- Registered workflow events page
- Workflow rules page
- Workflow event log and test processor
- Business/location-safe SQL tables
- Central service: `Services/Workflow/CommunicationWorkflowService.php`
- Message queue integration through `communication_hub_messages`

## SQL
Run this new SQL only in each tenant database:

- `Database/SQL/12_WORKFLOW_EVENT_ENGINE_UPGRADE.sql`

A master SQL file is also included:

- `Database/SQL/MASTER_ALL_COMMUNICATION_HUB_SQL_STAGE_009.sql`

## Notes
- No database name is hardcoded.
- Workflow event, rule and log data are scoped by `business_id` and `business_location_id` where available.
- ERP modules can trigger workflow events by calling `CommunicationWorkflowService::recordEvent()` and then `processEventLog()`.
