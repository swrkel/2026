# BKG-MFI-005 – Banking Microfinance Compliance, Risk & Recovery Extension

Standalone extension for the BankingMicrofinance module.

## Included
- KYC profile register and document verification tables.
- Compliance alert register for sanctions/PEP/document-expiry/manual exceptions.
- Risk assessment scoring service and risk grading shell.
- Collateral register with custody/release status.
- NPL case workflow and recovery action history.
- Approval matrix settings for amount/risk based multi-level approvals.
- Compliance, Risk, and NPL reports with standard toolbar layout.

## Installation
1. Copy `Modules/BankingMicrofinance` files into the existing module folder.
2. Register `Routes/web_bkg_mfi_005.php` from the module route service provider or include it from the module web route loader.
3. Run module migrations for the tenant database.
4. Publish/copy CSS and JS to `public/modules/bankingmicrofinance` if your asset pipeline does not auto-publish module assets.

## Safety Notes
- No existing working module files are overwritten except same-named BankingMicrofinance extension files.
- Tables use `bkg_mfi_` prefix and are standalone.
- Controllers are separated by Compliance, Risk, Recovery, Settings, and Reports namespaces.
