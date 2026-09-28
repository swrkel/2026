S415/S416 Customers Module fixes - 9 Jul 2026

Implemented fixes:
1. Customer Register -> Action -> Pay Due Amount
   - Payment Method dropdown is restricted to enabled Super Admin payment methods only.
   - Payment Account remains required and reloads based on selected payment method.
   - Bank/Cheque/Bank Transfer style methods now show Cheque No, Bank, Cheque Date fields.
   - Server-side validation blocks save until Payment Method, Payment Account, Amount, Date, and required bank/cheque fields are completed.
   - Cheque No, Bank and Cheque Date are saved to transaction_payments when the tenant schema has those columns.
   - After successful save, non-AJAX flow redirects to Customers Register.

2. Customer Register -> Action -> Edit
   - Existing standalone edit flow kept; opening balance synchronization already included in CustomerService.
   - No legacy Contacts controller/view dependency added.

3. S416 Customers module parity pages
   - Confirmed Customers routes for Customer Statement, Customer Payment, Outstanding Received, Edit Received Outstanding, Customer Payment Bulk, List Customer Payments, Customer Interest, Interest Settings, and Ledger Discount.
   - Customers Manage partial includes a local instant search field for Customers Module permissions/pages.

Changed files:
- Customers/Http/Controllers/CustomerPaymentController.php
- Customers/Services/CustomerPaymentActionService.php
- Customers/Resources/views/payments/form.blade.php
- Customers/Resources/views/superadmin/manage_customers_module.blade.php
