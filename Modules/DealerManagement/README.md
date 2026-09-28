# Dealer Management

Standalone Dealer Management module for the Laravel multi-tenant / multi-business ERP.

## Included
- Distributor-side Dealer Management pages
- Dealer-specific portal (`/dealer/login`)
- Multi-Distributor Dealer Hub (`/dealer-hub/login`)
- Central Hub dealer identity + 4-digit staff login codes
- Multiple distributors per dealer
- Distributor invitations / dealer connection requests / approvals
- Multiple Dealer Hub outlets and staff users
- Consolidated stock across distributors
- Distributor-specific source stock visibility
- FIFO / proportional / manual source allocation
- One daily sales entry for all distributors
- Split combined replenishment orders into distributor-specific orders
- Delivery and return feed aggregation
- Re-order rules, suggested quantities and notifications
- Distributor-side Hub Dealer Sales page
- Reports, audit-ready movement references, migrations and master SQL
- POS/system-standard design and existing performance optimizations

## Required central DB setting
Set one shared central database name in `.env` on the running application:

    DEALER_HUB_CENTRAL_DATABASE=nivasa_base

Use the actual central database name if it differs.

## Installation / upgrade
1. Replace `Modules/DealerManagement` with this parcel.
2. Run all Dealer Management migrations across central + tenants:

       php Modules/DealerManagement/Database/Scripts/migrate_all_databases.php

3. Verify Hub tables:

       php Modules/DealerManagement/Database/Scripts/verify_multi_distributor_hub.php

4. Clear caches:

       php artisan optimize:clear

5. Optional refresh:

       php artisan dealer-hub:refresh

See `Documentation/MULTI_DISTRIBUTOR_DEALER_HUB.md` for the complete operating sequence.
