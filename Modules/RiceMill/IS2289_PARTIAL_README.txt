IS2289 – Rice Mill – Partial Completed Parcel – 17 Sep 2026
============================================================

This parcel is intentionally limited to the items completed and statically validated so far.
It is based on the user's latest RiceMill.zip supplied on 16 Sep 2026.

COMPLETED / INCLUDED IN THIS PARCEL
-----------------------------------
1. Settings > Mills > Add Mill > Location
   - The supplied latest module already contains the business-wide active Location loading,
     Mill-specific request key, and tenant/business validation required for this screen.
   - This working baseline is retained in the FULL parcel.

2. Dashboard > Purchase Paddy > RM Location 2 / 3 / 4 save issue
   - Purchase Paddy now loads active Locations and Stores for the current tenant/business,
     instead of rejecting a valid Rice Mill Location because of a narrower user operational filter.
   - Server-side validation still rejects another business/tenant's Location or Store.
   - Receive Paddy was aligned to the same business Location/Store boundary so a related purchase
     from RM Location 2/3/4 can be received without its Store disappearing from the form.

3. Dashboard > Receive Paddy > Paddy Variety quality defaults
   - Hardened the Paddy Variety change event for Select2 + native selects.
   - Changing variety refreshes Moisture %, Foreign Matter %, Configured Limit, Quality Grade,
     and the Stock Lot preview from the selected variety.

4. Dashboard > Packing > Rice Product dropdown
   - Rice Product is now a searchable/type-to-filter dropdown using the module's existing Select2 standard.

5. Sales > Dispatched > Review/Approve > Print / Print Preview
   - Print-specific invoice font sizes were increased by 100% (doubled) for the requested larger print.
   - Existing landscape invoice structure is retained.

NO DATABASE SCHEMA CHANGES
--------------------------
No migration or SQL import is required for this partial parcel.

NOT INCLUDED YET
----------------
The remaining IS2289 cross-module work (Finance reports, Products New Stock Centre/History,
Agent Report, Product Category Mapping, Material Usage Mapping Settings tab, Category/Subcategory
Dispatch expansion, Supplier/Customer ledger direct posting, VAT integration, and two-way Products New
master synchronization) is NOT represented as completed in this parcel.

VALIDATION COMPLETED
--------------------
- PHP/Blade syntax checked across the complete module: 161 files passed php -l.
- RiceMill JavaScript passed node --check.
- ZIP integrity is checked before delivery.

DEPLOYMENT
----------
Take a backup of Modules/RiceMill first.
FULL parcel: extract so the RiceMill folder replaces/merges with Modules/RiceMill.
CHANGED FILES parcel: extract into the Modules folder and allow only the listed RiceMill files to overwrite.
No SQL import is required.
