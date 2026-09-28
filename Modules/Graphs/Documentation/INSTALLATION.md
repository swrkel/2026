GRAPHS MODULE - INSTALLATION
============================
1. Upload/extract the Modules/Graphs folder into the ERP root Modules directory.
2. From the ERP root run:
   php artisan module:enable Graphs
   php artisan optimize:clear
3. Sign in and open /graphs.
4. Super Admin > All Businesses > Manage / Manage Side Bar should discover the module automatically from module.json and Config/module_permissions.php.

Database changes: NONE.
Core file changes: NONE.
Existing module file changes: NONE.

DATA SOURCES (read-only)
- fuel_tanks: storage volume/current balance/location/product
- products: product name and alert_quantity used as re-order level
- transactions + transaction_sell_lines: final sales and sold quantities
- categories: non-fuel sub-category names

LOGIN ALERT
The module registers its own middleware. On the first authenticated HTML page request of a login session it displays a re-order alert when any tank current balance is <= its product alert_quantity. Failure in the analytics check is swallowed so it can never block login/navigation.


8021-2 FINANCIAL & PROFITABILITY PAGE
-------------------------------------
- /graphs/financial-profitability
- Daily Reconciliation & Cash Flow
- Profit Margin per Fuel Type
- Debtors Ageing Analysis with customer drill-down

Additional read-only sources used:
- settlements + settlement_cash_payments + settlement_card_payments + settlement_cash_deposits
- sw_settlements + sw_collections
- transaction_payments + contacts (debtor ageing)
- transaction_sell_lines_purchase_lines + purchase_lines + variations (fuel profitability cost)


## 8021-3 page
Operational & Loss Analytics: `/graphs/operational-loss`

No migration is required. After overwriting the consolidated parcel, run `php artisan optimize:clear`.

## 8021-4 Customer & Payment Analytics

Page 4 route: `/graphs/customer-payment`

No SQL or migration is required. After overwriting the existing Graphs module/public assets, run `php artisan optimize:clear`.
