Leads-New Stage 9 RC1
=====================
This package consolidates the standalone Leads-New module and adds the RC1 hardening layer.

Included in Stage 9:
- Release candidate dependency audit helper
- Independent sidebar/menu guard notes
- Report controllers and export/import shells
- Settings controllers for statuses, sources, priorities, tags and numbering
- API controller shells for mobile/external usage
- Middleware for module permission guard
- UI partials for action dropdown, filters, status badge and toolbar
- Final install/checklist document

Standalone rule:
The module is kept under Modules/LeadsNew and does not reuse the old Leads module files.
Only ERP platform-level services such as authentication, permissions, tenant DB connection,
sidebar registration and Laravel framework services are expected integration points.
