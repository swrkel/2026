# RestaurantNew RESTNEW_006 - Billing & Payments

This package adds the standalone RestaurantNew billing foundation.

## Included
- Bill, bill line, payment, and refund models.
- Billing service for bill creation, payment posting, voiding, and refunds.
- Billing controller and routes.
- Bill list, create, detail, receipt, payment, refund, and void views.
- Billing CSS and JS assets.
- Tenant/business/location-aware migration and separated SQL files.
- Idempotent permission insert SQL.

## Notes
- No shared/core tables are changed in this stage.
- All monetary columns use DECIMAL(22,4) to match ERP precision requirements.
- Routes are protected with auth and RestaurantNew business scope middleware.
