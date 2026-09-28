# RestaurantNew Stage 007 - Inventory & Recipe Costing

This package adds the standalone RestaurantNew inventory and recipe costing foundation.

## Included
- Ingredient categories
- Ingredient master
- Location-wise ingredient stock
- Stock movements / ingredient ledger
- Recipe headers and recipe lines
- Recipe cost recalculation service
- Automatic recipe consumption service foundation
- Wastage recording foundation
- Current stock, movements and wastage views
- Separate CREATE / ALTER / INSERT SQL files

## Tenant / Business Rules
All tables include `business_id`; stock tables include `location_id` to support multiple businesses and multiple locations per tenant database.

## Notes
This stage is independent inside `Modules/RestaurantNew` and does not place restaurant logic in shared files.
