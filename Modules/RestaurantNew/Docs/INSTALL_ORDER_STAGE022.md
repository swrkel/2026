# RestaurantNew Stage 022 Install Order

1. Upload the files in this package over the existing codebase.
2. Run `Modules/RestaurantNew/Database/SQL/create/022_create_online_ordering_tables.sql` on each tenant database.
3. Run `Modules/RestaurantNew/Database/SQL/insert/022_online_ordering_permissions.sql` on the database where permissions are stored.
4. Include or load `Modules/RestaurantNew/Routes/online.php` from the RestaurantNew route provider if not auto-loaded.
5. Clear route/config/view cache.
6. Test `/restaurant-new/online`, `/restaurant-new/online/menu`, `/restaurant-new/online/checkout`, and `/restaurant-new/online-admin/kitchen-queue`.
