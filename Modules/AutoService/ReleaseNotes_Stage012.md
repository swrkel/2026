# AutoService Stage 012 - Customer Portal Enterprise Features

## Added
- Customer portal invoice detail page with invoice lines and payment history.
- Customer portal invoice action link from invoice list.
- Business controlled customer portal settings:
  - Allow Customer Invoice PDF / Print
  - Allow Customer Fleet View
  - Allow Customer Feedback
  - Allow Customer Job Documents
- Customer/fleet vehicle list in the customer portal.
- Customer feedback capture from the portal.
- New `auto_service_feedback` table and entity.

## Kept intact
- Existing job status lookup by job number, vehicle number, and mobile number.
- Current invoice visibility remains controlled by business setting.
- Previous invoice visibility remains available for the customer.
- Workshop, invoice, payment, report, accounting readiness, and notification features from earlier stages remain consolidated.

## Installation
Replace the included AutoService module files, then run the application migration process used in your ERP.
