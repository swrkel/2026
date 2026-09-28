# PRODUCTSNEW_012 - Production Hardening & Final Standalone Audit

This parcel prepares Products New for testing as an enterprise-grade standalone module while keeping the existing legacy Product module untouched.

## Added

- Production Audit page: `/products-new/production-audit`
- Integration Bridge page: `/products-new/integration-bridge`
- Cross-module service bridge: `ProductsNewIntegrationBridge`
- Integration API endpoints:
  - `GET /api/products-new/integration/lookup`
  - `GET /api/products-new/integration/{product}/stock`
  - `GET /api/products-new/integration/{product}/price`
- Standalone audit service for legacy dependency scanning
- Security checklist service
- Performance checklist service
- UI standard service
- POS-style button text hardening CSS
- Stage SQL and updated master SQL

## Deployment Notes

1. Replace files from this parcel over the existing Products New stage files.
2. Run `ProductsNew_STAGE012_PRODUCTSNEW_012_PRODUCTION_HARDENING.sql` in each tenant database.
3. Keep the existing Product module enabled until Products New is fully tested and approved.
4. Use `/products-new/production-audit` to review standalone readiness.
5. Use `/products-new/integration-bridge` as the approved access pattern for POS, Purchasing, Distribution, Manufacturing and other modules.

## Safety Notes

- No legacy Product module files are changed.
- No database name is hardcoded.
- All new table names are prefixed with `products_new_`.
- The module still uses `/products-new` so it can run in parallel with the current Product module.
