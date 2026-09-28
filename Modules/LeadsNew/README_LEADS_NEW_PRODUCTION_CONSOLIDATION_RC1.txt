Leads-New Production Consolidation RC1 - 2026-07-05
=================================================

Purpose
-------
This parcel consolidates the Leads-New standalone module into one production branch based on the working Customers module architecture.

Deployment
----------
1. Backup current server folder: Modules/LeadsNew
2. Delete server folder: Modules/LeadsNew
3. Upload this parcel so the final path is exactly: Modules/LeadsNew
4. Run the SQL package on the ACTIVE TENANT DATABASE only.
5. Open /clear
6. Test in order:
   - /leads-new/route-ok
   - /leads-new
   - /leads-new/leads
   - /leads-new/leads/create
   - /leads-new/settings

Important
---------
Do not replace main routes/web.php or routes/tenant.php with this parcel.
This module follows the module-only route/provider pattern and is intended not to disturb other ERP modules.

Validation included
-------------------
- PHP syntax lint passed for all module PHP files.
- Route file controller/action references were checked.
- Main Leads CRUD route names exist.
- leadsnew and leads-new compatibility route aliases are retained.
- View namespace registration exists in the module service provider.
- Language namespace registration exists in the module service provider.
- Tenant middleware stack follows the Customers module pattern.

SQL
---
The SQL package is tenant safe and does not include a USE database_name statement.
Run it only after selecting the tenant database in phpMyAdmin/Adminer.
