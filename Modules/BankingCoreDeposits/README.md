# BKG-CORE-001 — Core Deposits Standalone Module

Standalone Core Banking Deposits module for Enterprise ERP Banking Suite.

## Scope
- Savings accounts
- Current accounts
- Fixed deposits
- Nominees and signatories
- Deposit/withdrawal/transfer transactions
- Interest accrual/posting engine
- Charges engine
- Standing instructions
- Account freeze/unfreeze, dormant, closure/reopen
- Dashboard and reports shells
- Permissions and language files

## Replacement paths
Copy `Modules/BankingCoreDeposits` into your Laravel `Modules` directory.
Copy public assets from `public/modules/banking-core-deposits` if your deployment process does not publish module assets automatically.

## Safety
This module is namespaced under `Modules\BankingCoreDeposits` and uses `bkg_core_*` tables only. It does not modify Petro, Finance, Contacts, Customers, MPCS, MyHealth, Insurance, or Microfinance modules.

## Suggested install sequence
1. Backup tenant database.
2. Copy files.
3. Run migrations for tenant DB.
4. Clear Laravel cache.
5. Assign permissions under Banking Core Deposits.
6. Open `/banking/core-deposits/dashboard`.

## Notes
The module includes clean service boundaries so later teller, cheque, ATM, internet/mobile banking modules can integrate without changing existing working functions.
