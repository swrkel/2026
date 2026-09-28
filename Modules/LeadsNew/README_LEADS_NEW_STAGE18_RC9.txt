Leads-New Stage 18 RC9
======================

Purpose:
- Production finalization layer for the standalone Leads-New module.
- Adds release validation, deployment checklist, server smoke test helpers, final permission/sidebar checklist, and multi-tenant verification helpers.

Scope:
- Does not depend on the old Leads module.
- Keeps all business logic inside Modules/LeadsNew.
- Uses only the common ERP framework requirements: auth, tenancy, permissions, routing, and module registration.

Recommended next step after upload:
1. Replace Modules/LeadsNew with this package content.
2. Run migrations/seeders as per your normal deployment process.
3. Open /leads-new/release/checklist.
4. Test dashboard, list, add, edit, reports, permissions, and sidebar visibility.
