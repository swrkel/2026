# RestaurantNew Stage 017 - Command Centers

Adds restaurant operational dashboards:
- Restaurant Command Center
- Kitchen Command Center
- Cashier Command Center
- Waiter Dashboard
- Manager Dashboard
- Executive Dashboard

The screens are standalone inside RestaurantNew and use RestaurantNew service/controller/view/assets only. Kitchen dashboard supports the requirement to show received orders and operational status, while waiter/cashier workflows remain linked to sale/order creation from prior stages.

Include `Modules/RestaurantNew/Routes/command_center.php` from the module route provider or RestaurantNew main route loader if not already auto-loaded.
