# IS1827 - Expenses New fixes

Date: 30 July 2026

1. Expense Summary Report now provides a standard DataTables toolbar with page-length selection, global search, and export/column buttons when the host DataTables Buttons extension is available.
2. Categories are rendered from the active business directly with the page, so a category saved through Add Category is immediately visible after redirect even if the host layout omits a pushed script stack.
3. The Category Default Payee Name control uses Select2 type-and-search. When Chequer is not explicitly enabled, `Cheque Module Not Enabled` is created locally, shown first, and selected by default for new categories.
4. Add Expense now renders and dynamically enforces only the Expense Account linked to the selected category. The controller also overwrites any posted account with the category-linked account.
5. Accounting Module is linked to Payment Method: Cash => Cash, Card => Card, Cheque => Cheque, and Bank Transfer => Bank. The server derives this value and does not trust the browser selection.
6. No database migration or SQL change is required for IS1827; the existing `accounting_module` column introduced in IS1824 is reused.
