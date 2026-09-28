# RestaurantNew Stage 044 - Equipment & Kitchen Asset Management

Includes equipment master, asset registration, preventive maintenance schedules, work orders, spare parts, alerts, kitchen health dashboard, and equipment reports.

Deployment order:
1. Run migration or SQL CREATE.
2. Run ALTER index SQL.
3. Run INSERT permission SQL.
4. Include `Routes/equipment.php` from the RestaurantNew route provider if it is not auto-loaded yet.
5. Compile/copy `equipment.js` and `equipment.css` according to the existing asset process.
