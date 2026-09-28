# Dealer Management - Installation and Testing

## 1. Upload
Place the folder as:
`Modules/DealerManagement`

Do not merge Dealer Management controllers/models/views with Distribution New.

## 2. Database
Use either Laravel migrations or the included master SQL.

Migration:
`php artisan migrate`

Manual SQL:
`Modules/DealerManagement/Database/SQL/DEALER_MANAGEMENT_MASTER.sql`

Run it in each tenant/central database that will use Dealer Management.

## 3. Clear caches
`php artisan optimize:clear`

If your deployment publishes module assets:
`php artisan vendor:publish --tag=dealermanagement-public --force`

The portal also contains an inline CSS fallback, so missing published CSS will not make it unusable.

## 4. Eligibility rule
The Dealer Login route checks whether Distribution New is enabled for the business. Primary detection is `disnew_module_ui_status` with:
- module_key = `distribution_new`
- is_visible = 1
- is_installed = 1

If that table does not exist, the adapter recognizes the presence of Distribution New tenant tables only when a business context is already known.

## 5. Create first dealer
ERP user opens:
`/dealer-management/dealers`

Create a Dealer and first Dealer Administrator. The module automatically creates:
- Dealer record
- Main Outlet
- Dealer Admin role
- Dealer Admin permissions
- 4-digit Login Code
- Temporary password

Copy the generated credentials at creation time.

## 6. Dealer login
Open:
`/dealer/login`

The dealer user enters:
- Business (when more than one eligible business is available)
- 4-digit Login Code
- Password

A newly created/reset account is forced to change its password.

## 7. Staff users
Dealer Admin opens **My Staff / Users** and can:
- create multiple staff users
- assign a role
- assign allowed outlets
- reset passwords
- activate/deactivate users

Dealer staff do not receive normal ERP authentication/session access.

## 8. 4-digit code
Each user gets an automatically generated 4-digit code unique inside that business. The user may change it from **Login Code**. Duplicate codes are rejected.

## 9. Distribution sync
Run manually from:
`/dealer-management/distribution-sync`

Or CLI:
`php artisan dealer-management:sync-distribution BUSINESS_ID`

Recommended production scheduling: run every 1-5 minutes using the application's scheduler/cron.

The sync imports only:
- `disnew_deliveries` with status `delivered`
- `disnew_returns` with status `approved` or `completed`

Each source transaction gets a unique `dlr_integration_events.event_key`, making the sync safe to run repeatedly without duplicate stock.

## 10. Dealer stock
Completed deliveries add dealer stock. Approved returns reduce dealer stock. Physical confirmations create `set` movements and retain variance/history.

## 11. Re-order alerts
Dealer Admin can configure per product/outlet:
- Minimum quantity
- Maximum quantity
- Re-order level
- Safety stock
- Stock-cover days
- Average daily usage

Suggested quantity uses maximum stock when configured; otherwise it uses average daily usage x stock-cover days + safety stock. Notifications are generated when stock reaches re-order level or physical confirmation is stale.

## 12. Security tests
Verify:
1. Dealer A cannot access Dealer B records by changing URL IDs.
2. Staff can only access assigned outlets.
3. Deactivated users cannot log in.
4. Wrong 4-digit code/password is rate limited.
5. Dealer login is unavailable when Distribution New is disabled.
6. Re-running sync does not duplicate stock.
7. Dealer Admin can reset staff password but cannot expose the old password.
8. Staff without `users.manage` cannot create/reset staff users.

## 13. Important deployment integration
The standalone module exposes the Dealer Login URL and enforces Distribution eligibility itself. If your main system login page has a module-link extension point, add the Dealer Login link there using your existing module menu/visibility renderer. Do not hard-code a Dealer link globally; render it only when `DistributionAvailabilityService::enabledForBusiness()` is true.
