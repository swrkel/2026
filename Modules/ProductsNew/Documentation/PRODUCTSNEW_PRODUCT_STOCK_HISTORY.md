# Product Stock History

New URL: `/products-new/stock-history`

Features:
- Date-range movement ledger and running balance.
- Opening, purchase, sale, purchase return, sale return, adjustment and transfer summaries.
- Location-wise and store-wise stock status.
- Reads Products New movement records and schema-compatible ERP purchase, sale, transfer and adjustment transactions.
- Tenant and business filtering is enforced.

SQL: run `Database/SQL/Feature_Product_Stock_History/01_Alter_Tables.sql`, then `02_Insert_Permission.sql`.
