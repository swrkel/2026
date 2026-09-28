# RestaurantNew Stage 009 - Delivery Management

This stage adds the standalone RestaurantNew delivery management layer.

## Included
- Delivery zones
- Delivery riders
- Customer delivery addresses
- Delivery order tracking
- Rider assignment
- Delivery status logs
- COD/card collection tracking
- Delivery summary report
- Rider performance report
- Migration and separated SQL files

## Tenant Safety
All tables include `business_id` and optional `business_location_id` so each tenant/business/location keeps its own delivery operations separated.

## Design Standard
Views follow the existing RestaurantNew/POS-standard card, toolbar and DataTable structure.
