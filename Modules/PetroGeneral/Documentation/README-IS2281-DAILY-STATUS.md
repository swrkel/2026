IS2281 – Petro General / Daily Status Report – 14 Sep 2026

Fixed issues
1. Page was moving/auto-scrolling while the user was reading the report.
2. Pump Sales Details could show the DataTables Ajax warning.

Changed system files
- PetroGeneral/Resources/views/daily_status_report/index.blade.php
- PetroGeneral/Http/Controllers/DailyStatusReportController.php

Scroll correction
- Removed the preDraw/draw scroll-position capture and window.scrollTo restoration.
- Restored native browser scroll anchoring.
- Kept one normal vertical page scroll and horizontal table scrolling only.

Pump Sales correction
- Removed collation-sensitive SQL joins between meter/payment settlement_no and settlements.
- Settlement references are now resolved safely for both historical formats:
  numeric settlements.id and textual settlements.settlement_no.
- Uses simple chunked WHERE IN lookups instead of CONVERT/COLLATE joins.
- Pump Sales AJAX always returns a valid DataTables JSON response even if an old tenant has an unexpected optional schema difference; the exact exception is logged server-side.
- Number formatting has a safe fallback.
- Pump Sales footer totals safely accept zero/numeric/string values.
- Blocking DataTables browser warning dialogs are disabled on this report page; any unexpected section error is shown inline instead.

Not changed
- No database changes / SQL / migrations.
- No report date/location rules changed.
- No settlement, tank, pump, accounting, payment-posting or permission logic changed.
- Print and PDF routes/logic unchanged.

After upload
cd /home/nivasa/public_html
php artisan optimize:clear

Then Ctrl+F5 and test Petro General -> Daily Status Report.
