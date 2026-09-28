# RestaurantNew RESTNEW 042 - Gift Cards & Vouchers

Adds standalone restaurant gift card and voucher management.

## Included
- Issue gift cards and vouchers.
- Track opening amount and live balance.
- Redeem against restaurant sales/bills.
- Top-up support in service layer.
- Full transaction ledger.
- Business and location scoped records.
- POS-standard list/detail screens.

## Deployment
1. Copy files into the ERP project.
2. Run migration or SQL in `Database/SQL/CREATE`.
3. Run permissions SQL from `Database/SQL/INSERT`.
4. Register route file if the RestaurantNew route provider does not auto-load sub-route files.

## Notes
This stage remains fully inside `Modules/RestaurantNew` and does not place voucher business logic in POS, Finance, Customers, or shared files.
