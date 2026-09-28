# BKG-MFI-007 Banking Microfinance Collections & Recovery Enterprise

Standalone extension for the Banking Suite Microfinance module.

## Scope
- Arrears / collection case register
- Collection action history
- Promise-to-pay tracking
- Recovery case workflow
- Legal action register
- Repossession tracking
- Collection dashboard/report services
- Independent routes, migrations, models, controllers, views, assets, permissions, language file

## Installation
Copy `Modules/BankingMicrofinance` into the ERP codebase, merge with the existing standalone BankingMicrofinance module folder, then run migrations through the normal deployment process.

## Safety
All new tables are prefixed `bkg_mfi_`. This parcel avoids modifying existing working ERP module files.
