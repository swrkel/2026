# Management Report Module - Static QA Report

- Passed: **44**
- Failed: **0**
- Module files: **90**

| Check | Result | Detail |
|---|---:|---|
| PHP syntax | PASS | 79 PHP files |
| modules_statuses.json | PASS | ManagementReport enabled |
| Section sales service | PASS | Services/Reports/Sections/SalesSectionService.php |
| Section sales view | PASS | Resources/views/daily/sections/sales.blade.php |
| Section operator_sales service | PASS | Services/Reports/Sections/OperatorSalesSectionService.php |
| Section operator_sales view | PASS | Resources/views/daily/sections/operator-sales.blade.php |
| Section add_less service | PASS | Services/Reports/Sections/AddLessSectionService.php |
| Section add_less view | PASS | Resources/views/daily/sections/add-less.blade.php |
| Section returns service | PASS | Services/Reports/Sections/ReturnsSectionService.php |
| Section returns view | PASS | Resources/views/daily/sections/returns.blade.php |
| Section financial_status service | PASS | Services/Reports/Sections/FinancialStatusSectionService.php |
| Section financial_status view | PASS | Resources/views/daily/sections/financial-status.blade.php |
| Section financial_status_two service | PASS | Services/Reports/Sections/FinancialStatusTwoSectionService.php |
| Section financial_status_two view | PASS | Resources/views/daily/sections/financial-status-two.blade.php |
| Section financial_breakup service | PASS | Services/Reports/Sections/FinancialBreakupSectionService.php |
| Section financial_breakup view | PASS | Resources/views/daily/sections/financial-breakup.blade.php |
| Section outstanding service | PASS | Services/Reports/Sections/OutstandingSectionService.php |
| Section outstanding view | PASS | Resources/views/daily/sections/outstanding.blade.php |
| Section stock_value service | PASS | Services/Reports/Sections/StockValueSectionService.php |
| Section stock_value view | PASS | Resources/views/daily/sections/stock-value.blade.php |
| Section pump_variance service | PASS | Services/Reports/Sections/PumpVarianceSectionService.php |
| Section pump_variance view | PASS | Resources/views/daily/sections/pump-variance.blade.php |
| Section dip_details service | PASS | Services/Reports/Sections/DipDetailsSectionService.php |
| Section dip_details view | PASS | Resources/views/daily/sections/dip-details.blade.php |
| Section final_review service | PASS | Services/Reports/Sections/FinalReviewSectionService.php |
| Section final_review view | PASS | Resources/views/daily/sections/final-review.blade.php |
| Selectable report sections | PASS | 12 sections |
| Controller DailyReportController | PASS |  |
| Controller DashboardController | PASS |  |
| Controller PublicShareController | PASS |  |
| Controller ReviewController | PASS |  |
| Controller SavedReportController | PASS |  |
| Controller SettingsController | PASS |  |
| Controller ShareHistoryController | PASS |  |
| Module table prefix | PASS | mgmt_report_templates, mgmt_report_runs, mgmt_report_run_sections, mgmt_report_shares, mgmt_report_share_recipients, mgmt_report_settings, mgmt_report_review_statuses |
| Module table count | PASS | 7 |
| Idempotent CREATE SQL | PASS | 7 CREATE TABLE IF NOT EXISTS |
| Idempotent INSERT SQL | PASS |  |
| No cross-module PHP class dependency | PASS | 0 references |
| No App model/controller dependency | PASS | 0 references |
| Super Admin discovery | PASS |  |
| Sidebar integration | PASS |  |
| Own public CSS/JS | PASS |  |
| Language files | PASS |  |

## Runtime limitation

The supplied source parcel does not include the Laravel root `artisan`, root `composer.json`, vendor dependencies, or a tenant database. Therefore route execution, migrations against a live tenant, mail/SMS gateway calls, and browser rendering could not be run in this workspace. PHP syntax, JavaScript syntax, JSON, section mappings, SQL idempotency, file independence, permissions, and sidebar integration were checked statically.
