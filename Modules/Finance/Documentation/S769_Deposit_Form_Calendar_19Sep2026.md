# S769 — Finance Deposit Form Calendar Fix — 19 Sep 2026

## Scope
- Finance Module → List Account → Cash Deposit → Calendar
- Finance Module → List Account → Card Deposit → Calendar

## Issue
The Cash Deposit and Card Deposit forms use the same `account/deposit.blade.php` view. The latest view had reverted to direct `bootstrap-datetimepicker` initialization even though Finance already contains the hardened shared native date binding. In the AJAX modal this old widget could be rendered oversized/misaligned inside the compact grid, matching the reported calendar display problem.

## Fix
1. Restored the shared Finance native date binding in `Resources/views/account/deposit.blade.php`.
2. Cash and Card Deposit now use the same stable browser-native `datetime-local` control already used by the Finance date helper.
3. Removed the direct bootstrap datetimepicker initialization from this deposit form only.
4. Preserved the posted `operation_date` format through the helper's hidden field, so no controller/database change is required.
5. Corrected the shared native helper to remove the old `readonly` attribute when converting legacy text fields. This is required for Cash/Card Deposit because those inputs were originally readonly for the old JavaScript calendar.
6. Existing deposit confirmation, overpayment/protected-account checks, attachment handling, Petro shift integration, and Cash/Card account logic were not changed.
7. Follow-up: increased only the Cash/Card Deposit modal width by exactly 15% from Bootstrap's 600px standard width to 690px on desktop, with a viewport-safe maximum width. This gives the calendar/date control enough horizontal space while leaving other Finance pop-ups unchanged.

## Changed files
- `Resources/views/account/deposit.blade.php`
- `Resources/views/account/partials/date_picker_bind.blade.php`

## Database
No SQL or migration required.

## Deployment
Replace the Finance module with this parcel and run:

```bash
php artisan optimize:clear
```

## Acceptance checks
1. Finance → List Account → Cash Deposit: click the Date field and calendar icon; date/time must open normally and remain selectable.
2. Choose another date/time and Save; confirmation must show the chosen value and the transaction must save with that value.
3. Repeat both checks for Card Deposit.
4. Re-open both forms after closing the modal; the date control must still work.
5. Confirm existing protected-account/overpayment rules still behave exactly as before.
