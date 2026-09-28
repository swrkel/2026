# CUS-007 - Customer Register Actions Popup Fix

## Purpose
Open Customer Register row action functions in a popup modal, similar to Contacts > Customers action buttons, while keeping action routes/controllers/views inside the Customers module.

## Files changed
- Modules/Customers/Resources/views/index.blade.php
- Modules/Customers/Resources/views/partials/actions.blade.php
- Modules/Customers/Resources/views/layouts/action.blade.php
- Modules/Customers/Http/Controllers/CustomerPaymentController.php
- Modules/Customers/Http/Controllers/CustomerLoanController.php
- Modules/Customers/Http/Controllers/CustomerRefundController.php
- Modules/Customers/Http/Controllers/CustomerDepositController.php

## What changed
- Added `.customers-action-popup` action links.
- Added `.customers_action_modal` modal container.
- Added AJAX modal loader for customer actions.
- Added modal-specific action layout for AJAX requests.
- Kept full page layout when the action URL is opened directly.
- Kept delete as a confirmation action, not a popup.

## Test steps
1. Open Customers Module > Customer Register.
2. Click Actions on a row.
3. Click Pay Due Amount, Advance Payment, Loan to Customer, Refund Deposit, Security Deposit, Refund Payment, Cheque Return.
4. Confirm each opens in a popup.
5. Click View Customer, Edit Customer, Balance Details, Contact Info, Ledger, Notes, Attachments, Audit Trail.
6. Confirm each opens in a popup.
7. Confirm Close button closes popup and the register page remains open.

## Clear cache
php artisan optimize:clear
php artisan view:clear
