# BKG-MFI-003 Banking Microfinance Accounting & Controls Extension

Standalone extension for the BankingMicrofinance module. This parcel adds accounting-safe operational controls without replacing existing working files unless the paths are copied intentionally.

## Includes
- Repayment allocation engine: fees -> penalties -> interest -> principal
- Write-off and recovery workflow shells
- Loan loss provisioning rules and generation
- Field officer target tracking
- Audit events and exception report
- Portfolio quality reports: PAR, NPL, write-off, recovery, provisioning

## Install order
1. Copy the `Modules/BankingMicrofinance` folder into your ERP root.
2. Register routes from `Routes/web_bkg_mfi_003.php` in your module route provider.
3. Run migrations after backup.
4. Clear cache.

## Safety
All tables use `bkg_mfi_` prefix. Controllers, models, services and views are namespaced inside BankingMicrofinance only.
