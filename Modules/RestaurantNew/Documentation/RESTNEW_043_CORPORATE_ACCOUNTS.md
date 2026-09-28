# RestaurantNew RESTNEW 043 - Corporate Accounts

Adds corporate account and credit billing foundation for RestaurantNew.

## Included
- Corporate account master
- Credit limit and credit days
- Contract price foundation
- Monthly invoice foundation
- Invoice lines linked to restaurant orders
- Corporate payment tracking
- Corporate statement report base
- Standalone routes, controller, service, views, assets, permissions, migration, and SQL

## Deployment
1. Copy files into the project.
2. Load `Modules/RestaurantNew/Routes/corporate.php` through the RestaurantNew route provider when route auto-loading is not enabled.
3. Run migration or SQL from `Database/SQL/CREATE` on each tenant database.
4. Run permission SQL from `Database/SQL/INSERT`.
5. Assign permissions to the required roles.
