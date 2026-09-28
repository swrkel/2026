# IS2349 – MPCS – 27 Sep 2026

Target: MPCS module, ishadi.nivasa.

## Corrections

1. **F21 – redundant row removed**
   - Removed the extra business/F21 header row shown between the filters and the F21 information row.
   - Preserved the F21 Print action by moving the button into the Form Information row.
   - Print button is now `type="button"` so printing cannot submit/reload the form.

2. **F21 – selected date / transaction type details**
   - Restored the pre-regression F21 client-side loading flow used before the recent DataTable event changes.
   - `All` still routes to `/mpcs/get-all-f21-transactions`.
   - POS Sale, Settlement, Purchase Order, Sales Return and Purchase Return continue to use their dedicated endpoints.
   - All F21 data endpoints now resolve the tenant business ID consistently.
   - Form-number lookup is non-blocking: an auxiliary form-number problem is logged but cannot stop transaction rows from loading.
   - The `All` aggregator now strips outer DataTables `columns`, `order`, `search` and paging metadata while collecting each source, then restores the outer request before the combined DataTable response. This prevents source-specific SQL aliases from being affected by the combined table request.

3. **F20 – print preview**
   - Reworked the DataTables print view for A4 landscape.
   - Added a clean report header with location, F20 title, report subtitle, form number, date and form type.
   - Table expands to the available printable width, uses black borders/text, repeated header styling and adaptive print font size.
   - Manager Signature is included in Print and PDF.

4. **F20 – duplicate horizontal slider**
   - Removed the custom range slider and its scroll-sync event logic.
   - Retained the table's normal/native horizontal scrollbar as the single horizontal slider.

5. **F20 – Manager Signature**
   - Added a Manager Signature section below the F20 table on the live form.
   - The same signature area is included in the print preview and PDF export.

No database schema change is required.
