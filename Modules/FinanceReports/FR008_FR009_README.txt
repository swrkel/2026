FinanceReports FR008/FR009 - Core Reporting Engine + Executive BI

Purpose
- Continue Finance Reports as larger consolidated releases.
- Add a shared reporting engine so current and future reports use common context, branch/consolidated handling, statement pack, and executive KPI data.
- Keep the existing Finance module untouched.

Added
- Executive BI Dashboard - New
- Report Engine Status
- FinanceReportEngine
- FinanceReportContext
- BranchConsolidationService
- CurrencyFormatterService
- New sidebar entries under Finance Reports
- New routes:
  /finance-reports/executive-bi-dashboard-new
  /finance-reports/report-engine-status

Safety
- Read-only module.
- No posting/updating/deleting of finance records.
- No replacement of current Finance module pages.
- Uses standalone Modules/FinanceReports namespace.

Branch / Consolidated
- location_id empty/all = consolidated.
- selected location_id = branch/location wise.
