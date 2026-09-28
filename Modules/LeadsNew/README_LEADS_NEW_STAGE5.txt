LEADS-NEW STAGE 5 LARGE PARCEL - 2026-06-29

Added operational standalone components:
1. Lead conversion service and conversion history table.
2. Opportunity pipeline table, model, controller, service and view.
3. Standalone quotation table, model, service, controller and view.
4. Import/export batch table and CSV export framework.
5. Activity/audit log table and audit service.
6. Stage 5 routes under /leads-new only.
7. Requests, policy, reminder job and console command skeletons.

No existing Leads module files are used by this package. The module stays inside Modules/LeadsNew.
Required platform-level dependencies remain Laravel auth, tenancy/session business_id, permissions and module registration.
