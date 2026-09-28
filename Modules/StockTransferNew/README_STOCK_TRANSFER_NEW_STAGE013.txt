StockTransfer-New STN_013 - Performance & Production Optimization

This parcel adds performance monitoring and optimization helpers for large multi-tenant installations.

Included:
- Performance dashboard
- Query health view
- Tenant/business/location/store-safe filter helper
- Cache warm/clear control
- Export queue tracking for large reports
- Optimized summary service for command-center/report pages
- Index SQL for high-volume transfer, line, approval, movement, scan, audit, and report tables
- New permission: stocktransfernew.performance

Important:
- Product master is still not duplicated. Product data must continue to come from the existing standalone Products module/bridge.
- SQL should be run on tenant databases only unless clearly marked otherwise.
