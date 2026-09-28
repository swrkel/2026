# IS2342 – MPCS – 25 Sep 2026

Basis: IS2342 issue document, checked in ishadi-pd with DB 157.

## 1. F20 Form / F20 Form Details
- Kept the AJAX selected-date product quantities visible in the live table.
- Removed DataTables logic that could initialise all product columns as hidden and leave only Bill No / Settlement No visible.
- Preserved the earlier rule that products with no selected-date transactions are excluded from Print/PDF/Excel/CSV through the export-column filter.
- Existing F20 toolbar/search/rows/column visibility features are retained.

## 2. F9C Credit Form / F9C Credit Details
- Removed the fixed-height nested whole-page scroll host that could trap mouse-wheel/page scrolling.
- Restored normal document vertical scrolling.
- Enabled DataTables horizontal scrolling for the wide report without creating a nested vertical table scroller.

## 3. F21 Form
- Prevented the outer DataTables columns/order/search payload from being passed into each internal source endpoint when Transaction Type = All.
- This avoids source-specific SQL alias/order/filter conflicts while combining POS, Settlement, Purchase, Sales Return and Purchase Return details.
- Disabled automatic server-side ordering for the mixed-source table and corrected the rows-per-page "All" option to use -1.
- Kept horizontal scrolling and column adjustment so the full report remains reachable.
- Added business-id fallback for the All/F22 opening-stock collection path.

## 4. F21C Form / Print Preview
- Print preview now writes the server-generated print document directly into the print window instead of mixing it with the application screen CSS.
- All report text is forced to black only.
- Print table text remains at 28px (100% increase from the former 14px baseline), with fixed table layout and wrapping to avoid the browser shrinking the report unnecessarily.
- The print window waits until content is loaded before invoking print and closes only after printing.

## Validation performed
- PHP/Blade syntax check: 235 files, 0 failures.
- F21FormController.php passed PHP syntax validation.
- No merge-conflict markers were found.
- No database/SQL changes are required for IS2342.

Note: Runtime verification against DB 157 must be performed on the target Laravel installation because this package does not include the tenant database/runtime environment.
