# CUS-026 Distribution Dealer Delivery Tracking

## Scope
Adds Distribution Dealer portal delivery tracking inside `Modules/Customers` only.

## Included
- `CustomerPortalDeliveryController.php`
- Portal routes for `/distribution-dealer/deliveries`
- Delivery list page
- Delivery detail / print page
- Portal navigation link

## Safety
This package does not modify Petro, PetroPD, Finance, PumperDashboard, or Contact module files.

## Test
1. Upload files preserving folder paths.
2. Run:
   ```bash
   php artisan optimize:clear
   php artisan view:clear
   ```
3. Login as Distribution Dealer.
4. Open `Deliveries` from the portal menu.
5. Test search/status/date filter, View, and Print.
