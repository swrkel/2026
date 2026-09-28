Rice Mill - Paddy Variety Add/Edit product mapping
Date: 18 Sep 2026

Implemented:
1. Paddy Variety Name is now the first field.
2. The Name field is a searchable/scrollable dropdown populated only with active Products from the Product Category mapped as Paddy in Rice Mill > Settings > Product Category Mapping.
3. Paddy Variety Code is automatically loaded from the selected Product Code / SKU and is read-only.
4. Server validation derives Name and Code again from the shared Products table, so request tampering cannot change the code.
5. Add and Edit both use the same behavior.
6. Existing legacy varieties are matched by Name + Code in Edit where possible.

Database:
Import Database/SQL/RiceMill_Paddy_Variety_Product_Link_18Sep2026.sql into each existing tenant DB, or run the module migration. Fresh installs are covered by RiceMill_Master_SQL.sql and the base migration.

After deployment:
php artisan optimize:clear
