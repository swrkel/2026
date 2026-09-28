# BKG-MFI-004 – Banking Microfinance Field Operations Extension

Standalone parcel for the BankingMicrofinance module. This parcel adds field collection operations, center-day workflow, device sync tracking, offline collection batch upload, receipt verification, supervisor approval, and field productivity reports.

## Important
- This parcel is independent and does not modify existing Banking, Finance, Petro, Customer, or core ERP files.
- All database tables use the `bkg_mfi_` prefix.
- Routes are isolated in `Routes/web_bkg_mfi_004.php`.
- Controllers, services, views, assets, and language files are under `Modules/BankingMicrofinance` only.

## New areas
1. Field officers and route plans
2. Center-day collection sheet
3. Offline collection batches and lines
4. Receipt verification queue
5. Supervisor approval / rejection
6. Device sync log
7. Field cash handover
8. Reports: daily field collection, officer productivity, receipt exceptions, cash handover variance

## Suggested install
1. Copy `Modules/BankingMicrofinance` into the project.
2. Include `Routes/web_bkg_mfi_004.php` from the BankingMicrofinance service provider or module route loader.
3. Run migrations after backup.
4. Clear caches.

## Permission keys
- banking_microfinance.field.dashboard
- banking_microfinance.field.route_plans.view
- banking_microfinance.field.route_plans.create
- banking_microfinance.field.collection_sheets.view
- banking_microfinance.field.collection_sheets.create
- banking_microfinance.field.offline_batches.view
- banking_microfinance.field.offline_batches.approve
- banking_microfinance.field.receipts.verify
- banking_microfinance.field.cash_handover.view
- banking_microfinance.field.cash_handover.approve
- banking_microfinance.field.reports.view
