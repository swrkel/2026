# AutoService Stage 030 - Customer Bill, Payment History and Export Tools

## Added
- Customer printable service summary from the portal.
- Customer payment history page with invoice, job, receipt/reference, method, amount, status and note.
- CSV export for parts/accessories usage history with applied filters.
- Reset filter shortcut for the parts/accessories history.
- Portal settings for service summary print, parts export and payment history visibility.

## Improved
- Customer portal now gives a stronger end-to-end view of current service, bill, past history, parts/accessories usage, payment history and downloadable records.
- Added tenant-safe SQL without hardcoded database names.

## Files touched
- Http/Controllers/CustomerPortalController.php
- Routes/web.php
- Resources/views/customer_portal/partials/lookup_content.blade.php
- Resources/views/customer_portal/payment_history.blade.php
- Resources/views/customer_portal/print/service_summary.blade.php
- SQL/31_AUTOSERVICE_STAGE030_CUSTOMER_BILL_PAYMENT_EXPORTS.sql
