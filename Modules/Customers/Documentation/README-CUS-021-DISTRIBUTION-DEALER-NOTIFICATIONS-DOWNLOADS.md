# CUS_021 Distribution Dealer Notifications + Download Center

## Scope
- Adds Distribution Dealer Notification Center.
- Adds Download Center for Statements, Invoices, Payment Receipts and Credit Notes.
- Adds print views for customer invoices and payment receipts.
- Adds portal navigation links for Notifications and Downloads.

## Safety
- All changes are inside Modules/Customers only.
- Does not change Petro, PetroPD, PumperDashboard, Finance, Contact module, or ERP sidebars.
- Customer portal remains read-only and restricted to the logged-in dealer/customer.

## Test
1. Open `/distribution-dealer/login`.
2. Login using a customer passcode.
3. Check Dashboard, Notifications, Downloads, Invoices and Payments.
4. Print one invoice and one payment receipt.
5. Confirm the dealer cannot see any other customer's records.
