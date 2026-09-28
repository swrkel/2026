# StockTransfer-New STN_029 - Logistics Execution Guide

This parcel adds logistics execution support after stock transfer approval/dispatch planning.

## Included
- Route master table for transfer route planning.
- Multi-vehicle load headers and lines.
- Driver, assistant, ETA and vehicle capacity fields.
- Dispatch and receiving checklist JSON storage.
- Transfer consolidation header and lines.
- CSV export for vehicle loads.

## Notes
- This does not duplicate product master data.
- Product/variation/SKU fields are only copied as transfer execution snapshots.
- GPS field is a placeholder for future mobile/GPS integration.
- Run the tenant SQL in every tenant database where StockTransfer-New is enabled.
