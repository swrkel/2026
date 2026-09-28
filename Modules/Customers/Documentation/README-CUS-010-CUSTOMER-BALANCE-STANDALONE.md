CUS-010 Customer Balance Standalone Fix

Changed files:
1. Customers/Http/Controllers/CustomerBalanceController.php
   - New standalone Customer module controller for Balance Details action.
   - Does not call ContactController.
   - Uses ContactUtil balance calculation only to keep totals consistent with existing ERP logic.

2. Customers/Resources/views/balance/index.blade.php
   - Full replacement balance popup view inside Customers module.
   - Matches Contact Customer balance fields: Total Sale, Opening Balance, Total Paid, Balance Due.
   - Keeps Customer module modal layout and selected business font.

3. Customers/Routes/web.php
   - customers.balance route now points to CustomerBalanceController@show.

Test:
- Customers Module > Customer Register > Actions > Balance Details
- Confirm popup opens.
- Confirm totals match Contacts > Customers > Actions > Balance Details.
- Confirm popup closes correctly.

After upload run:
php artisan optimize:clear
php artisan view:clear
