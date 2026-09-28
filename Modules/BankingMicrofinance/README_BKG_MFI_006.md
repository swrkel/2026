# BKG-MFI-006 – Enterprise Lending & Credit Management

Standalone BankingMicrofinance extension. This parcel does not depend on other ERP module files and uses `bkg_mfi_` prefixed tables.

## Included
- Credit bureau provider register, bureau enquiries, and API log tables.
- Loan origination stages and application timeline tracking.
- Cashflow and affordability analysis service.
- Collateral valuation and guarantor assessment registers.
- Risk-based loan pricing rule engine.
- Credit committee decision register.
- Enterprise reports: credit pipeline, cashflow affordability, guarantor exposure, pricing matrix, credit committee.
- Separate controllers, routes, views, services, models, assets, permissions, language file, and migrations.

## Install
1. Copy `Modules/BankingMicrofinance` into your ERP codebase.
2. Include `Routes/web_bkg_mfi_006.php` from the BankingMicrofinance route loader.
3. Run tenant migrations.
4. Publish/copy `Resources/assets` to `public/modules/bankingmicrofinance` if your module asset publisher is not automatic.

## Notes
- All pages use the approved toolbar pattern: Search, Date Range, CSV, Excel, PDF, Print, Column Visibility.
- Shell pages are intentionally safe and can be connected to DataTables without breaking current working functionality.
