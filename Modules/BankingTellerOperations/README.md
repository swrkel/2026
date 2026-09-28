# BKG-CORE-002 — General Banking / Teller Operations

Standalone enterprise teller/cash-counter module for the Banking Suite.

## Scope
- Teller dashboard and daily cash drawer workflow
- Cash counters and teller cash limits
- Drawer opening, cash-in/out, denomination tracking
- Deposit/withdrawal/transfer teller slips
- Supervisor authorization queue
- Cash differences and explanations
- Vault request and branch cash transfer workflow
- Teller end-of-day balancing and close
- Teller audit trail and operational MIS reports

## Installation
Copy `BKG-CORE-002_GeneralBanking_TellerOperations` to `Modules/BankingTellerOperations` or keep the folder name and register its service provider according to your module loader.

Run migrations after confirming the tenant database connection:

```bash
php artisan migrate --path=Modules/BankingTellerOperations/Database/Migrations
```

## Safety
This parcel does not alter existing ERP files. It uses only its own routes, controllers, services, views, assets, permissions, language files, migrations and entities.

## Main Route
`/banking/teller`
