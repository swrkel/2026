# Dealer Management - Multi-Distributor Dealer Hub

## Purpose
A dealer who buys from several distributors uses one Dealer Hub login and enters daily sales only once. The Hub separates each product line by distributor and writes only the relevant allocation back to that distributor's Dealer Management data.

## Main architecture
- The Dealer Hub identity and cross-distributor relationships are stored in one central database.
- Every distributor remains a separate tenant/business and retains its own private prices, invoices, orders, deliveries, credit/outstanding and local dealer stock.
- The Hub stores only the shared dealer identity, distributor links, consolidated product-source stock, dealer-entered sales allocations, split-order references and Hub notifications.
- A distributor never receives another distributor's commercial data.

## Required environment setting
Set this in the application `.env` so every tenant knows where the shared Hub tables live:

    DEALER_HUB_CENTRAL_DATABASE=nivasa_base

Replace `nivasa_base` if the actual central database has another name.

The MySQL user used by the application must have permission to read/write the configured central database and the tenant databases that the ERP already manages. This is required because Hub synchronization writes each dealer allocation/order back to the correct distributor tenant database.

## New URLs
Distributor / ERP:
- `/dealer-management/dealer-hub`

Dealer Hub:
- `/dealer-hub/login`
- `/dealer-hub/dashboard`
- `/dealer-hub/stock`
- `/dealer-hub/daily-sales`
- `/dealer-hub/product-sources`
- `/dealer-hub/reorder`
- `/dealer-hub/orders`
- `/dealer-hub/deliveries`
- `/dealer-hub/returns`
- `/dealer-hub/reports`
- `/dealer-hub/users`
- `/dealer-hub/outlets`

## Distributor setup sequence
1. Open Dealer Management > Multi-Distributor Dealer Hub.
2. The system generates/registers the Distributor System Code automatically.
3. Either create a new Dealer Hub identity or select an existing Hub dealer.
4. Link the Hub dealer to the matching local Dealer record and create the invitation.
5. Dealer accepts the invitation from My Distributors, or the dealer requests a connection using the Distributor System Code.
6. For a dealer-initiated request, distributor selects the matching local Dealer and approves.
7. If the dealer has multiple outlets, map each local Dealer outlet to the matching Hub outlet.
8. Dealer clicks Refresh in Dealer Hub. Products and source balances are synchronized.

## One daily sales entry
Dealer enters Sold, Return and Damage quantities once in Daily Sales Entry. For products supplied by more than one distributor, allocation can be:
- FIFO
- Proportional
- Manual distributor selection

Each allocation updates only the relevant distributor's local dealer stock and creates a `hub_dealer_sale` stock movement for traceability.

## Combined orders
The dealer enters one combined order. Each product can use Auto/Best Source or a Preferred Distributor. The Hub automatically creates separate local Dealer Management orders in the appropriate distributor databases.

## Re-order alerts
Re-order levels can be set per product source. The Hub calculates consolidated current quantity, consolidated re-order level and suggested quantity. Re-order notifications are regenerated during Hub Refresh and after rule changes.

## Dealer staff users
Dealer Hub Admin can create multiple staff users. Every user receives an auto-generated 4-digit login code and temporary password. Users can be limited by permissions and selected Hub outlets. Dealer Hub Admin can reset staff passwords.

## Security
- Hub users are scoped to one Hub Dealer.
- Outlet scope is enforced from `dlr_hub_user_outlets`.
- Distributor connections must be active before product data is synchronized.
- Each distributor allocation is written only to the configured distributor database/business/local dealer.
- Distributor-side approval is required for dealer-initiated connection requests.

## Migrations
New migrations:
- `2026_09_27_000004_create_multi_distributor_dealer_hub.php`
- `2026_09_27_000005_add_multi_distributor_hub_indexes.php`

Run across central and all tenant databases:

    php Modules/DealerManagement/Database/Scripts/migrate_all_databases.php

Verify central Hub tables:

    php Modules/DealerManagement/Database/Scripts/verify_multi_distributor_hub.php

Then clear caches:

    php artisan optimize:clear

## Optional automatic refresh
Manual:

    php artisan dealer-hub:refresh

Specific Hub dealer:

    php artisan dealer-hub:refresh 12

This command is suitable for Laravel Scheduler / cron if automatic refresh is required.
