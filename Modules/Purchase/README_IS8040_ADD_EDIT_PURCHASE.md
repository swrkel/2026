# IS8040 – Add/Edit Purchase

Applied to `/purchase/entries/create` and the edit page (which uses the same current create view/controller flow).

Changes:
1. Product-table and payment-table column-heading fonts reduced by 2 points (12px to 10px).
2. Unit Cost, Cost Inc. Tax and Selling Price display with 4 decimals to match the supplied ProductsNew List Product page.
3. Tax column reduced from 160px to 96px (40%).
4. Standalone SKU column removed; SKU is displayed beneath Product / Variation.
5. Received Qty and Current Tank Balance in Unload Tanks now use Business Settings > Currency Precision.
6. Existing calculations, field names, validation and save/update services were left intact.

No SQL/database change is required.
