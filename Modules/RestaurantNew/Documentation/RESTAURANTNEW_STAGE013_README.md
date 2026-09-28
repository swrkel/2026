# RestaurantNew Stage 013 - Kitchen Production System

## Included
- Live kitchen production board.
- Kitchen queue records for all received orders.
- Status flow: received, preparing, ready, served, cancelled.
- Preparation timers and production logs.
- Priority handling: low, normal, high, urgent.
- Kitchen routing rules by location, order type, menu category, and menu item.
- Kitchen performance logs for reporting.
- Separate migration and SQL files.

## Important
This stage is self-contained inside `Modules/RestaurantNew`. It does not alter other modules except for optional SQL linking to RestaurantNew kitchen ticket item records.
