# IS2286 – MPCS fixes – 15 September 2026

This update addresses every distinct item in `IS2286 – MPCS – 15 Sep 2026.docx`.

## Implemented fixes

1. **F9C Credit details page scrolling**
   - Restored normal document scrolling on the nested F9C Credit tab page.
   - Removed fixed-height/overflow constraints inherited from the application theme.

2. **F21 related details not showing**
   - Corrected the F22 opening-stock query that failed under MySQL `ONLY_FULL_GROUP_BY`.
   - Tenant-scoped the product-name join to prevent cross-business matches.
   - Made the All-transactions aggregator request every source without pagination and restore the original DataTables request state after each source.
   - Added safe client-side handling for empty and failed responses.

3. **F25 Goods Issued form table**
   - Converted the entry table to a compact fixed layout that fits the available desktop width.
   - Reduced cell/input metrics and removed the horizontal-scroll requirement.

4. **F20 CDS print preview**
   - Increased the print-only type, row, spacing, and heading sizes.
   - The A4 landscape preview now uses the printable page area instead of rendering a tiny report at the top.

5. **F20 form settings table**
   - Compact fixed table layout keeps all columns visible.
   - Long category/product lists are contained within short wrapping cells so one record cannot make the page excessively tall.

6. **F9A Settings – Payment section**
   - Disabled DataTables horizontal scrolling for the two settings tables.
   - Fixed both tables to the available width with compact wrapped headings; the six Payment headings fit within two lines at normal desktop widths.

7. **F25 opening date display**
   - Added a reliable model display accessor and used it in all three F25 settings displays.
   - A saved opening date now renders using the configured business date format, with a safe raw-value fallback.

8. **F20 print button**
   - Added a Print button to the F20 page using the report's existing print stylesheet.

## Database changes

No database migration or SQL execution is required for this update.

## Validation performed

- Confirmed all embedded JavaScript blocks in the changed Blade views parse successfully after Blade placeholders are substituted.
- Checked all changes for whitespace errors.
- Verified referenced routes, controller methods, translation keys, and table column mappings.
- Verified both delivery ZIP archives with `unzip -t` after packaging.
