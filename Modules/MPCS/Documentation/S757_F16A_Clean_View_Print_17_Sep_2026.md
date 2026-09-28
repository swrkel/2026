# S757 - F16A clean View / Print Preview / Print

Date: 17 Sep 2026

Scope: presentation only. No F16A calculations, totals, data loading, tenancy, routes, controller queries or database structures were changed.

Changes:
- Added a dedicated full-width DataTables totals slot between the report rows and info/pagination.
- Removed the Bootstrap negative-margin condition that could clip the first label and the far-right total.
- Rebuilt the F16A summary block with stable 27/23/27/23 percentage columns.
- Kept Form 9C's 12px Arial report/table font standard.
- Added consistent table borders, spacing, alternating-row treatment and a clear grand-total row.
- Normalized report/header/table/summary widths for screen and A4 landscape print.
- Normalized Bootstrap row margins inside the print area so the report cannot extend beyond either print edge.
- Updated the popup print stylesheet to match the on-screen report instead of producing a different layout.
