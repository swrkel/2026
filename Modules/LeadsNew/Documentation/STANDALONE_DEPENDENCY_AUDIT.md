# Standalone Dependency Audit

Target: `Modules/LeadsNew`

The module should not depend on the old Leads module. The following references must not exist:

- `Modules\\Leads\\`
- `Modules/Leads/`
- `Leads\\Http\\Controllers`
- `Leads\\Models`
- `leads.` route names from the old module
- `resources/views/leads`
- old leads JavaScript or CSS assets

Allowed shared platform dependencies:

- Laravel framework services
- Authentication
- Authorization / permissions
- Multi-tenant database connection
- Business and branch context
- Module registration
- Common application shell/layout

These are platform services required by every ERP module and are not business-module dependencies.
