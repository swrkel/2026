# Global ERP Development Standard v1.0

This standard is introduced from Communication Hub RC1 and should be reused by future standalone modules.

## 1. Dashboard standard
Every major module dashboard should use the ERP Dashboard Standard (EDS v1.0):

1. Professional module header with breadcrumb and quick actions.
2. 4-column KPI grid on desktop, 2 columns on tablet, 1 column on mobile.
3. Eight clickable KPI cards where practical.
4. Analytics panels below KPI cards.
5. Recent activity / operational status panel.
6. Quick operations strip for daily actions.

## 2. Page completion checklist
A page is not considered ready until it has:

- Professional CoreUI style.
- Working route and controller action.
- Tenant database-safe queries.
- Business-level filtering where applicable.
- Validation for create/update forms.
- Permission guard where applicable.
- Empty-state messages instead of blank screens.
- Search/filter/export where the page is report/list based.
- Operation steps included in documentation.

## 3. SQL deployment standard
Every module package should include:

- Laravel migrations.
- Raw SQL for tenant deployment.
- Permission SQL.
- Default data SQL where required.
- Index SQL where required.
- Rollback SQL where safe.

Deployment SQL must not use stored procedures, triggers, definers, or delimiter blocks.

## 4. Module registration standard
Every standalone module should provide a module registration file declaring:

- Module name and version.
- Sidebar title and icon.
- Route groups.
- API route groups.
- Permission groups.
- Dashboard widgets.
- SQL version.

## 5. UI standard
All modules should reuse CoreUI presentation components where possible. Business logic must remain inside the owning module.

## 6. Multi-tenant standard
Operational data must come from the active tenant database. Central database access is only for central/admin/platform-level data.

## 7. API standard
External integrations should use versioned APIs, starting with `/api/v1/...`.
