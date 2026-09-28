# RESTNEW 041 - Enterprise Reservation & Floor Management

This package adds standalone RestaurantNew reservation and floor-management foundations.

## Included
- Floor plans and table designer data structures
- Reservation booking and check-in flow
- Waitlist and table assignment flow
- Live table status logging
- Dashboard summary service
- POS-standard views/assets
- Permission and SQL files

## Deployment
1. Copy files to the same paths in the Laravel project.
2. Load `Modules/RestaurantNew/Routes/reservation.php` from the RestaurantNew route loader if it is not already autoloading all route files.
3. Run the migration or execute SQL from `Database/SQL/CREATE` and `Database/SQL/INSERT`.
4. Clear route/config/view cache.
5. Assign the new permissions to restaurant roles.
