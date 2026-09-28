LEADS-NEW STAGE 6 LARGE PARCEL - 2026-06-29

Added another standalone layer under Modules/LeadsNew only:
1. Workflow automation tables, model, service and controller.
2. Email/SMS/WhatsApp/internal message template tables, model, service and controller.
3. Calendar event table, model, service, controller and views.
4. API token table and API lead controller skeleton.
5. Bulk action controller for status updates and assignment.
6. Additional reports: Lead Ageing and Team Performance.
7. Stage 6 config, language, JS, CSS and seeder.

No existing Leads module files are used. No existing working functions are changed.
Only normal ERP/Laravel shared platform services are expected: auth, tenancy/session business id, permission middleware and module loading.
