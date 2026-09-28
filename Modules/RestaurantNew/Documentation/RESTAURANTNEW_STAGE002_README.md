# RestaurantNew Stage 002

Core setup implementation for the standalone Restaurant-New module.

## Included
- Restaurant Settings page.
- Dining Areas CRUD.
- Tables CRUD with dining area link and table status.
- Kitchen Sections CRUD.
- Order Types CRUD.
- Numbering sequence setup for Order, KOT and Bill numbers.
- Stage 002 migration and separated SQL files.

## Notes
- All data is scoped by business and optional location.
- No shared RestaurantNew business logic was added outside the module.
- Next stage should implement Menu Management: categories, menu items, variants, modifiers, kitchen routing and availability.
