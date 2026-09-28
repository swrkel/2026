# IS2346 – MPCS – 27 Sep 2026

Basis: IS2346 issue document, checked in ishadi.nivasa with DB 157. Changes are limited to the four reported regressions.

## 1. F20 Form – selected-date sold products only
- The live F20 table now hides configured product columns that have no sale for the selected date/form type.
- A product is treated as sold only when the selected-date response has a non-zero quantity or amount; zero/placeholder meter rows do not create empty columns.
- The same active-product set is applied to the raw table, DataTables column state, Column Visibility, Print, PDF, Excel and CSV.
- The configured product list is not changed, so another date can immediately show a product again when it has sales.
- Stale DataTables visibility state is overridden on every reload/reinitialisation.

## 2. F9C Credit – professional two-row table heading
- Removed DataTables `scrollX` from this report because it clones the table header and was breaking the two-row heading alignment.
- Native `.table-responsive` now owns horizontal scrolling.
- Rebuilt the second heading row with proper `<th>` cells instead of `<td>` cells inside `<thead>`.
- Added stable heading alignment/vertical centering so Bill No, Our Ref, Product Name, Qty, Unit Price and Page span both heading rows cleanly, while Total Amount/Goods/Loading/Empty/Transport/Others show `Amount` on the second row.

## 3. F9C Credit – scrolling jump
- Removed the repeated resize/DataTables-draw logic that rewrote `html`, `body`, wrapper and content heights while the user was scrolling.
- F9C now uses one stable vertical scroll owner with `overflow-anchor: none` and `overscroll-behavior-y: contain` to stop automatic up/down jumping.
- Horizontal movement remains isolated to the table-responsive wrapper.
- Print mode releases the scroll limit so printed content is not clipped.

## 4. F21 Form – selected date / selected transaction details
- Restored the pre-regression F21 source and All-transaction aggregation path from the known working baseline.
- Removed the recent `_f21_aggregate` optimisation that changed starting/balance calculations and the internal DataTables request shape.
- Restored normal source calculations for POS Sale, Settlement, Purchase Order, Sales Return and Purchase Return before the All report combines them.
- The current single-reload date/type UI handling, horizontal table access and corrected All page-length selector are retained.
- Existing tenant/business fallback for F22 opening stock is retained.

## Database
No SQL, migration or database structure change is required for IS2346.

## Deployment
Replace the MPCS module with the FULL parcel, then run:

```bash
php artisan optimize:clear
php artisan view:clear
```

Hard refresh the browser (Ctrl+F5) before testing against DB 157.
