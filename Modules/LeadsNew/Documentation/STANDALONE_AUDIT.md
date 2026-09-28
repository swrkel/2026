# Leads-New Standalone Audit

Target: Leads-New must not depend on the existing Leads business module.

Allowed shared platform services:
- Laravel framework
- Authentication
- Authorization/permissions
- Tenant/database resolver
- Business/location context
- Common layout shell
- Module registration

Disallowed dependencies:
- Existing Leads controllers
- Existing Leads models
- Existing Leads services
- Existing Leads repositories
- Existing Leads views
- Existing Leads JS/CSS
- Existing Leads language files
- Existing Leads routes
