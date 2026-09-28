# Finance Reports Reference Implementation for Enterprise Framework

This package connects the standalone `FinanceReports` module to the standalone `EnterpriseFramework` module through a read-only adapter.

## Important Safety Rules

- Existing `Finance` module files are not changed.
- Existing operational accounting pages are not replaced.
- `FinanceReports` remains read-only.
- The Enterprise Framework discovers reports through `FinanceReportsEnterpriseAdapter`.
- The adapter exposes report metadata, dashboard metadata, health status, and safe read-only data hooks.

## Added Files

- `Modules/FinanceReports/Services/Adapters/FinanceReportsEnterpriseAdapter.php`
- `Modules/FinanceReports/Services/Framework/EnterpriseFrameworkBridgeService.php`
- `Modules/FinanceReports/Config/financereports_enterprise.php`

## Purpose

Finance Reports becomes the reference implementation for future modules such as PetroPD Reports, Distribution Reports, Membership Reports, Customers Reports, and MyHealth Reports.
