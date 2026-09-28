IS2318 - PAYMENT OPTIONS - FINAL END-TO-END FIX
Date: 24 Sep 2026

Scope fixed
===========
1. Purchase -> Add Purchase / Edit Purchase
   - Payment Method list now comes from business_locations.default_payment_accounts.
   - Only Active methods with Purchases enabled are offered for the selected Business Location.
   - Custom/new Payment Options are included automatically.
   - Changing Business Location refreshes the payment-method list immediately.
   - Existing historical method remains visible on initial Edit when later disabled.
   - Existing Credit Purchase (Due) workflow is preserved.
   - Add Purchase Payment now uses the same location-specific Purchase payment options.
   - Hard-coded request validation was removed so configured custom methods can be saved.

2. ExpensesNew -> Add Expense / Edit Expense
   - Removed hard-coded Cash/Card/Cheque/Bank Transfer dropdown.
   - Payment Method list now comes from business_locations.default_payment_accounts.
   - Only Active methods with Expenses enabled are offered for the selected Business Location.
   - Custom/new Payment Options are included automatically.
   - Changing Business Location refreshes payment methods and linked Finance accounts immediately.
   - Linked Finance account/account-group mapping is read from the selected Payment Option.
   - Server-side validation confirms the method is enabled for Expenses at that location and confirms the selected account is linked to it.
   - Historical disabled methods/accounts remain editable when unchanged.

3. Compatibility
   - No dependency on Superadmin classes was added to Purchase or ExpensesNew.
   - Older locations with no default_payment_accounts configuration fall back to the existing legacy method list.
   - No SQL / migration required.

Deployment
==========
A. Full combined bundle:
   Extract into the application's Modules directory so Purchase/ and ExpensesNew/ overwrite the existing module files.

B. Or install the two full module ZIPs individually.

C. After deployment run:
   php artisan optimize:clear

Validation completed
====================
- 557 PHP files linted successfully across the two corrected modules (excluding Blade templates).
- Modified Purchase JavaScript passed node syntax check.
- ZIP archives tested after creation.
