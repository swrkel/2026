# Leads-New Stage 10 RC2 Delivery Notes

This parcel continues the standalone Leads-New module and adds production-hardening items without touching the existing Leads module.

Included:
- Standard report hub and report views.
- CSV export service.
- Document center with upload/delete.
- Settings model and settings screen for numbering/defaults.
- Bulk assignment and bulk status services/controllers.
- RC2 CSS and JavaScript assets.
- Migration for documents/settings.

Standalone rule:
- No references to the old Leads module are introduced.
- Shared ERP use remains limited to authentication, tenant connection, permissions and Laravel module registration.

Suggested test order:
1. Run migrations on a test tenant.
2. Open Leads-New dashboard.
3. Open Reports > Lead Register.
4. Test CSV export.
5. Upload and delete a test document.
6. Save numbering/default settings.
7. Test bulk status/assignment using AJAX endpoint.
