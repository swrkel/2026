# RESTNEW 029 - Production & Central Kitchen

This stage adds standalone RestaurantNew production planning, semi-finished item handling, batch production, yield tracking, wastage costing, and branch distribution foundation.

## Main flows
1. Create production plan.
2. Approve production plan.
3. Start batch production.
4. Complete batch with yield payload.
5. Distribute produced/semi-finished items to branches.

## Tenant rule
All tables include `business_id` and optional `location_id`. Run CREATE SQL in tenant databases only.
