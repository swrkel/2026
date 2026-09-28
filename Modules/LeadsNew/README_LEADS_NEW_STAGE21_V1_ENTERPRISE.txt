Leads-New Stage 21 v1.0 Enterprise Release Package

Scope:
- Final consolidated release engineering package for the standalone Leads-New module.
- Adds deployment checklist, production readiness checklist, route smoke validation, and module health checks.
- Keeps all Leads-New business logic under Modules/LeadsNew.
- Does not modify the existing Leads module.

Important:
- Shared ERP services such as authentication, tenancy, permission checking, and module registration remain framework-level dependencies required by all modules.
