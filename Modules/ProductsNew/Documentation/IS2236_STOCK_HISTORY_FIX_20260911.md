# IS2236 - Products New Stock History Fix - 11 Sep 2026

## Scope

Products New -> List Product -> Action -> Product History Report
Products New -> Stock Center -> Stock History

Affected sections:
- Opening Stock Qty / Opening columns
- Location-wise Stock Status
- Store-wise Stock Status
- Detailed Product Movement Ledger

## Fixes completed

1. Opening stock entered while adding a product is retained as opening stock in Stock History.
   - `opening_stock` is shown in the Opening Stock Qty KPI and the Opening columns.
   - It is not counted again as Qty In.
   - This applies to Location-wise, Store-wise and Detailed Product Movement views.

2. Transactions with no explicit store are treated as belonging to the applicable `Main Store` for Stock History reporting.
   - This is a reporting-time mapping only; no historical stock rows are rewritten.
   - It preserves compatibility with older tenant schemas and legacy rows where `store_id` is NULL.
   - Selecting Main Store includes those rows instead of hiding them.
   - Selecting All Stores still shows explicitly assigned alternative stores separately.

3. Detailed Product Movement Ledger continues to pin true opening-stock rows at the top.

4. The `Location` column has been removed from Detailed Product Movement Ledger as requested.
   - Location filtering and Location-wise Stock Status remain unchanged.
   - Ledger column widths were rebalanced after removing the column.

5. Stock History CSS browser cache version was advanced to IS2236.

## Database

No SQL or database schema change is required for IS2236.

## Deployment note

The Stock History page loads the browser stylesheet from:
`public/modules/productsnew/css/stock-history.css`

Use the supplied consolidated application-root parcel so the module and the public CSS are deployed together.

After deployment run:
`php artisan optimize:clear`

Then hard-refresh the Stock History page once if it was already open in the browser.
