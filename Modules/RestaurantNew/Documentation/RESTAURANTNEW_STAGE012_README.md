# RestaurantNew Stage 012 - Advanced POS and Table Operations

This package adds the advanced Restaurant POS operation layer while keeping all logic inside `Modules/RestaurantNew`.

## Included
- Hold and resume order flow.
- Split bill foundation with split header and split lines.
- Multiple payment recording for one order or split bill.
- Transfer table.
- Change waiter.
- Merge running orders.
- Table operation audit trail.
- Advanced POS UI page.
- Tenant/business/location indexes in all new tables.

## Important
Run CREATE SQL first, then ALTER SQL, then INSERT SQL for permissions. If you use Laravel migrations, the migration covers both CREATE and ALTER logic.
