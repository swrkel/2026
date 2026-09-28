# Auto Service Stage 010 - Customer Invoice Portal

## Added
- Customer portal now shows invoice history for the matched customer/vehicle/job.
- Invoice details are shown inline, including line items, quantities, rates, totals, payment method, reference, status and amount.
- Customer can see previous invoices by searching with Job No, Vehicle No, Mobile No, VIN, Chassis No or Engine No.
- Current job invoice is protected by business setting.

## New Setting
- Auto Service Settings > Allow Customer to View Current Invoice
  - Default: No
  - When disabled, previous invoices are visible but invoice(s) linked to the current active job are hidden from the customer portal.
  - When enabled, customer can see current invoice details too.

## Changed Files
- AutoService/Http/Controllers/CustomerPortalController.php
- AutoService/Http/Controllers/SettingsController.php
- AutoService/Resources/views/customer_portal/partials/lookup_content.blade.php
- AutoService/Resources/views/settings/index.blade.php
- AutoService/Database/Migrations/2026_06_29_000010_add_customer_invoice_portal_setting.php
