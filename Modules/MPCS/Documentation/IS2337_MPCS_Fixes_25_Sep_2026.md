# IS2337 – MPCS – 25 Sep 2026

Regression-focused corrections applied to the MPCS module supplied as `MPCS(20260925-074036).zip`.

## 1. F20 Form / F20 Form Details
- Preserves the system-standard DataTables/report toolbar introduced previously.
- Destroys the existing F20 DataTable before AJAX replaces the selected-date rows, preventing DataTables from restoring stale rows/hidden column state.
- Detects active product columns from the actual selected-date detail rows, with totals keys as a fallback.
- Keeps configured products intact while showing only products that have related details for the selected date/form type.

## 2. F9C Credit Form / F9C Credit Details
- Replaces the conflicting nested tab-content vertical scroller with one page-level scroll host.
- Keeps the wide details table horizontally scrollable while allowing the complete F9C Credit page to scroll vertically.
- Print mode releases the scroll constraints.

## 3. F9A Form / Daily Cash and Sales Report
- Keeps dynamic product columns at readable widths instead of squeezing/hiding right-side columns.
- Adds a dedicated horizontal range slider below the sales table.
- Slider stays synchronized with native horizontal table scrolling and recalculates after AJAX data loads/resizes.

## 4. F21 Form
- Removes duplicate AJAX reloads caused by the date-range callback plus apply event and overlapping filter handlers.
- Adds horizontal DataTables scrolling so the full table remains accessible.
- Clears the Processing overlay on request errors instead of leaving the page visually stuck.
- Optimizes the “All transaction types” aggregation: source endpoints skip repeated starting/balance/form-number calculations only during internal aggregation, then the combined rows receive the existing running-balance calculation once. Direct source filters retain their previous calculation path.

## 5. F21C Form / Print Preview
- Forces print text and table details to black only.
- Doubles the previous 14px table print font to 28px (100% increase).
- Uses A3 landscape print layout to provide room for the larger multi-column report.
- Corrects the document title from F22 to F21C.

## 6. F22 Stock Taking / F22 Form / Print Preview
- Pumps & Meters is now rendered only when the outer product chunk is the last print page.
- Applied to both the normal F22 print partial and the print-by-ID partial.
- Other existing totals/signature/footer behavior is left unchanged.

## Database
No SQL, migration, or database structure change is required for IS2337.
