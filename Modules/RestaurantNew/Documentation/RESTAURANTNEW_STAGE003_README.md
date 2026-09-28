# RestaurantNew Stage 003 - Menu Management

This parcel adds the standalone menu management foundation for RestaurantNew.

## Included
- Menu categories with parent category support.
- Menu items with category, kitchen section, SKU, price, cost, tax, preparation time, and availability flags.
- Menu variants.
- Menu modifiers / add-ons.
- Recipe/ingredient foundation for the later inventory consumption stage.
- Tenant/business/location scoped services and controllers.
- POS-standard list/form UI pages.
- Separate migration and SQL files.

## Database
Run the Stage 003 CREATE SQL in each tenant database where RestaurantNew is enabled, or run the Laravel migration.

No shared/legacy table changes are made in this stage.
