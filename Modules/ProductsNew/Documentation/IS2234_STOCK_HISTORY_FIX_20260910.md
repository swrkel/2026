# IS2234 - Products New Stock History Fix - 10 Sep 2026

## Scope

Products New -> List Product -> Action -> Product History Report
Products New -> Stock Center -> Stock History

Affected sections:
- Location-wise Stock Status
- Store-wise Stock Status
- Detailed Product Movement Ledger

## Fixes completed

1. Opening stock is now treated as an opening balance in the Location-wise Stock Status table.
   - A true `opening_stock` movement dated inside the selected report range is shown in `Opening`.
   - It is no longer counted again in `Qty In` or `Net Change`.
   - Closing remains `Opening + Net Change`.

2. The same opening-stock treatment is applied to Store-wise Stock Status.

3. On the initial Stock History page load, the active store named `Main Store` is selected by default when it exists.
   - If a location is already supplied, the matching Main Store for that location is preferred.
   - If the user explicitly chooses `All Stores`, that selection is respected.
   - If no Main Store exists, the report keeps the existing All Stores behaviour.

4. Detailed Product Movement Ledger now has a separate `Opening` column.
   - Opening stock quantity is shown only in the new Opening column.
   - The same quantity is not repeated in Qty In.
   - Opening-stock rows are pinned at the top of the detailed ledger.
   - Other transactions remain latest-first.

5. Stock History CSS column widths were rebalanced for the new Opening column, and the asset cache key was updated.

## Reconciliation example

For the sample shown in IS2234:
- Opening stock: 100.000
- Other Qty In: 58.000
- Qty Out: 30.000
- Net Change: 28.000
- Closing: 128.000

The corrected Location-wise row therefore displays:
`Opening 100.000 | Qty In 58.000 | Qty Out 30.000 | Net Change 28.000 | Closing 128.000`

## Database

No SQL or database schema change is required for IS2234.

## Deployment note

The Stock History page loads the browser stylesheet from:
`public/modules/productsnew/css/stock-history.css`

Therefore either:
1. Use the supplied application-root consolidated parcel, which contains both the full fixed module and the updated public CSS; or
2. If replacing only the ProductsNew module, also copy `ProductsNew/Resources/assets/css/stock-history.css` to `public/modules/productsnew/css/stock-history.css`.

After deployment run:
`php artisan optimize:clear`

Then hard-refresh the Stock History page once if it was already open in the browser.
