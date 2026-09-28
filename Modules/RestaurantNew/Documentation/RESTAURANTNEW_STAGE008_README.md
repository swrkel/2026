# RestaurantNew Stage 008

Adds standalone staff and shift control foundations.

## Included
- Staff member master with waiter/cashier/kitchen/manager roles.
- Cashier shift opening and closing.
- Cash in / cash out movements.
- Tips recording table.
- Service charge distribution table and calculation service.
- Staff performance and cashier shift reports.
- Separate CREATE / ALTER / INSERT SQL files.

## Notes
- All tables are tenant/business scoped using `business_id` and optional `location_id`.
- No existing non-RestaurantNew module files are changed.
- POS design standard is preserved through RestaurantNew-specific views/assets.
