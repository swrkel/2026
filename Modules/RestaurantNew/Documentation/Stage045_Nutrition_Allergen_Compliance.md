# RestaurantNew Stage 045 - Nutrition, Allergens & Recipe Compliance

Includes allergen master, dietary tags, menu nutrition profiles, item/allergen mapping, recipe compliance checks, customer allergy warning logs, reports, permissions, migration and SQL.

Deployment order:
1. Copy files.
2. Run migration or SQL CREATE script in tenant databases.
3. Run permission INSERT script in the permissions database/table used by the application.
4. Optionally run default allergens/tags insert per business after replacing `:business_id`.
5. Add/ensure `Routes/compliance.php` is loaded by the RestaurantNew module route provider.
