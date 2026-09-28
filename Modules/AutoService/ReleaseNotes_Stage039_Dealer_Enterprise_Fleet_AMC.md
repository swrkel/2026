# Auto Service Stage 039 - Dealer Enterprise / Fleet / AMC

## Added
- Dealer Enterprise Centre page.
- Fleet / corporate customer registry.
- Fleet service and AMC contract tracking.
- Fleet driver management.
- Corporate pricing visibility.
- Fleet job register with CSV export.
- Optional dealer/trade-in table for future vehicle sales integration.

## Multi-tenant / Multi-business Safety
- All new tables include `business_id` and `location_id`.
- Controller filters by active business and active business location.
- Dealer enterprise features are optional and permission-controlled.

## New SQL
- `40_AUTOSERVICE_STAGE039_DEALER_ENTERPRISE_FLEET_AMC.sql`
- Master SQL updated: `00_MASTER_AUTOSERVICE_ALL_SQL_STAGE020_TO_STAGE039.sql`

## New Permissions
- `autoservice.dealer_enterprise.view`
- `autoservice.dealer_enterprise.manage`
- `autoservice.fleet_customer.view`
- `autoservice.fleet_customer.manage`
- `autoservice.fleet_contract.view`
- `autoservice.fleet_contract.manage`
- `autoservice.corporate_pricing.view`
- `autoservice.corporate_pricing.manage`
