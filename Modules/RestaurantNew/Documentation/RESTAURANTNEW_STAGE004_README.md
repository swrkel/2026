# RestaurantNew Stage 004 - Restaurant POS Foundation

This package adds the first standalone Restaurant POS foundation.

## Included
- Dine-in / Takeaway / Delivery POS screen foundation
- Running order panel
- Order, order line, and payment entities
- Restaurant POS service for order creation, line addition, recalculation, and payment posting
- Controller endpoints for running orders, order creation, item addition, payment, and close
- Tenant/business-aware table structure
- Separate migration and SQL files
- POS-standard CSS/JS foundations

## Notes
- This stage is standalone inside `Modules/RestaurantNew`.
- No shared Restaurant business logic is added outside the module.
- KOT, split/merge/transfer tables, and full billing screens continue in later stages.
