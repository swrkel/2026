# Communication Hub Stage 008 - Automation Engine

This stage adds an event-driven Communication Automation Engine for the ERP.

## Included
- Automation Dashboard
- Automation Rules page
- Automation Events page
- Test event generation
- Pending event processing
- Multi-channel rule execution: SMS, Email, WhatsApp, Push, In-App
- Business-wise and location-wise data scope
- Queue linkage using automation_rule_id and automation_event_id

## SQL
Run `database/sql/11_AUTOMATION_ENGINE_UPGRADE.sql` in every tenant database.
The master SQL file is also updated.
