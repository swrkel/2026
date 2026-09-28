# ATN-002 Core Master Setup

Includes fully isolated CRUD foundations for:
Airlines, Airports, Aircraft Types, Travel Classes, Routes, Suppliers, Agents,
Currencies, Tax Rules and Commission Rules.

## Apply
1. Merge this parcel over ATN-001.
2. Include `Routes/masters.php` inside the existing authenticated ATN route group.
3. Load `Resources/lang/en/masters.php` under the module translation namespace or merge its keys into messages.php.
4. Publish/copy the two ATN-002 assets.
5. Run the migration or `Database/SQL/MASTER/MASTER_ATN_002_CORE_MASTERS.sql`.
6. Assign the ATN-002 permissions.
7. Run `php artisan optimize:clear`.

No legacy Airline tables, controllers, views, routes or route names are used.
