# S755 – MPCS F16A Form display and printing fixes – 17 Sep 2026

Scope: `MPCS / F16A Form` only.

## Fixes

1. **Full page fits the screen**
   - Removed the F16A horizontal-scroll layout (`scrollX`).
   - Removed the old 1450px minimum table width and 18px forced table text that caused the report to overflow.
   - Uses a fixed 100% table layout with controlled percentage widths across all 11 columns.
   - Long product/location/reference values wrap inside their own columns instead of expanding the page.

2. **Business Location remains selected**
   - Removed the JavaScript that cleared the already-selected/default location to `All` after Select2 initialization.
   - The initial AJAX request now uses the same location shown in the Business Location field.
   - Changing the date preserves the selected location; changing the location reloads F16A using the selected date + location.

3. **Professional Date / Business / Form No heading**
   - Rebuilt the report heading as a three-part responsive header: Date, Business + Location, F16A Form No.
   - Initial values are visible immediately; server AJAX continues to update the form number and date as before.

4. **Full print preview**
   - F16A print uses A4 landscape at full width.
   - Removed the old print rule that hid the last report column.
   - Forces DataTables/table wrappers to full width with no overflow or clipped right side.
   - Print-only font/padding sizes allow all F16A columns to fit on the printed page.

## Changed application files

- `MPCS/Resources/views/forms/F16A.blade.php`
- `MPCS/Resources/views/forms/partials/16a_form.blade.php`

No F16A calculation, database, controller, route, save, pagination, totals, or tenancy logic was changed.
