# BKG-MFI-008 – Banking Microfinance Treasury & Liquidity

Standalone extension for the Enterprise ERP Banking Suite.

## Included
- Branch vault register and balances
- Vault movement ledger
- Inter-branch treasury transfers with submit/approve/post workflow
- Funding lines and available limit control
- Daily liquidity snapshot
- Liquidity position and gap-analysis report shells
- Treasury reports: daily liquidity, vault movement, funding utilization
- Own routes, migrations, models, controllers, services, views, assets, lang, permissions

## Safety
This parcel does not modify existing ERP, Finance, Customers, Petro, or previous Banking files. Copy `Modules/BankingMicrofinanceTreasury` into the Laravel `Modules` directory and register the service provider/module using your existing module loader.

## Suggested install order
1. Backup system and tenant database.
2. Copy module folder.
3. Enable module in your module loader.
4. Run migrations for tenant database.
5. Clear config/routes/views cache.
6. Assign permissions to required roles.

## Next roadmap
BKG-MFI-009 – Core Deposits: savings/current/fixed deposits, deposit products, teller-safe account opening, interest accrual, maturity, lien/hold, statements and reports.
