# Stock Taking - New — STK_001

A standalone Laravel module for multi-tenant, multi-business, multi-location and multi-store stock counts.

## Main capabilities

- Full, cycle and spot stock takes
- Blind or open counting
- Location-level or store-level stock snapshots
- Multiple assigned counters
- Initial counts, variance-controlled recounts and complete count history
- Submission, approval, rejection and stock reconciliation posting
- CSV count import
- Count templates and recurring schedules
- Dashboard, progress, variance, stock accuracy and audit reports
- Print view and PDF stream/download
- Secure expiring document links
- SMS, email and WhatsApp delivery
- Business-specific Manage Page discovery and direct-URL blocking
- Granular Spatie user permissions

All module-owned data tables use the `stk_` prefix. The PHP code does not import or call classes from the legacy Stocktaking, Products, ProductsNew, Stock Adjustment or Stock Transfer modules. Shared product/location/store/stock master data is accessed only through the module-owned bridge services.
