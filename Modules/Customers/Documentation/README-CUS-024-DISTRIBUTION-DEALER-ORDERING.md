CUS-024 Distribution Dealer Ordering Portal

Upload paths:
- Modules/Customers/Http/Controllers/CustomerPortalOrderController.php
- Modules/Customers/Routes/portal.php
- Modules/Customers/Resources/views/portal/partials_nav.blade.php
- Modules/Customers/Resources/views/portal/products.blade.php
- Modules/Customers/Resources/views/portal/place_order.blade.php
- Modules/Customers/Resources/views/portal/order_show.blade.php
- Modules/Customers/Database/sql/CUS_024_customer_portal_orders.sql

Before testing:
1. Run the SQL file in the tenant database.
2. Upload replacement files.
3. Run:
   php artisan optimize:clear
   php artisan view:clear

Test:
1. Login as Distribution Dealer.
2. Open Products.
3. Open Place Order.
4. Add one or more products with quantities.
5. Submit order.
6. Verify order detail page opens.

Notes:
- This keeps dealer orders inside Customers module tables customer_portal_orders and customer_portal_order_lines.
- It does not post ERP sales orders automatically yet, so it will not affect Petro, PetroPD, Finance, or Contact workflows.
